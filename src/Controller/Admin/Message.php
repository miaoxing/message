<?php

namespace Miaoxing\Message\Controller\Admin;

class Message extends \miaoxing\plugin\BaseController
{
    /**
     * 实时消息列表
     */
    public function indexAction($req)
    {
        switch ($req['_format']) {
            case 'json':
            case 'csv':
                $group = $req['group'] ?: 'today';

                $messages = wei()->message();

                // 分页
                $messages
                    ->limit($req['rows'])
                    ->page($req['page']);

                // 排序
                $messages->desc('id');

                // 查询比指定记录更新的记录
                if ($req['lastId']) {
                    $messages->andWhere('id > ?', $req['lastId']);
                }

                // 查询指定用户的聊天记录
                if ($req['userId']) {
                    $messages->andWhere('userId = ?', $req['userId']);
                }

                if ($req['platformId']) {
                    $messages->andWhere(['platformId' => $req['platformId']]);
                }

                // 限制来源, 1表示排除公众号的回复消息
                if ($req['source']) {
                    $messages->andWhere('source = ?', $req['source']);
                }

                // 隐藏关键词消息
                if ($req['fromKeyword'] === '0') {
                    $messages->andWhere('fromKeyword = 0');
                }

                // 按消息类型筛选
                if ($req['type']) {
                    $messages->andWhere('msgType = ?', $req['type']);
                }

                switch ($group) {
                    case 'starred':
                        $messages->andWhere('starred = 1');
                        break;

                    case 'today':
                        $messages->andWhere('createTimestamp > ?', strtotime(date('Y-m-d')));
                        break;

                    case 'all':
                    default:
                        // 设置最后阅读时间
                        wei()->message->setLastReadMessageTime();
                        break;
                }

                // 搜索内容
                if ($req['search']) {
                    $messages->andWhere('content LIKE ?', "%{$req['search']}%");
                }

                $data = [];
                $account = wei()->wechatAccount->getCurrentAccount()->toArray(['id', 'nickName', 'headImg']);
                foreach ($messages->findAll() as $message) {
                    if ($message['source'] == 1) {
                        // 用户发送的消息
                        $user = wei()->arrayCache->get('user' . $message['userId'], function () use ($message) {
                            $user = wei()->user()->findOrInitById($message['userId']);
                            return $user->toArray(['id', 'nickName', 'headImg']);
                        });
                    } else {
                        // 公众号回复的消息
                        $user = $account;
                    }

                    // 合并消息
                    $data[] = ['user' => $user] + $message->toArray();
                }

                if ($req['_format'] == 'csv') {
                    return $this->renderCsv($data);
                } else {
                    return $this->json('读取列表成功', 1, array(
                        'data' => $data,
                        'page' => $req['page'],
                        'rows' => $req['rows'],
                        'records' => $messages->count(),
                    ));
                }

            default:
                return get_defined_vars();
        }
    }

    /**
     * 与用户交流界面
     */
    public function userAction($req)
    {
        $user = wei()->user()->findOneById($req['userId']);

        return get_defined_vars();
    }

    protected function renderCsv($messages)
    {
        $data = array();
        $data[0] = array('用户', '消息内容', '消息类型', '时间', '是否收藏');
        $types = wei()->message->getOption('types');

        foreach ($messages as $message) {
            $data[] = array(
                $message['nickName'],
                is_string($message['content']) ? $message['content'] : '(不支持显示)',
                $types[$message['msgType']],
                $message['createTime'],
                $message['starred'] ? '是' : '否'
            );
        }

        return wei()->csvExporter->export('messages', $data);
    }

    /**
     * 实时向指定用户发送消息
     */
    public function sendAction($req, $res)
    {
        // 1. 校验提交的数据
        $type = $req['type'] ?: 'text';
        $validator = wei()->validate(array(
            'data' => $req,
            'rules' => [
                'userId' => 'required',
                'content' => 'required',
                'appMsgId' => [
                    'required' => $type == 'news'
                ]
            ],
            'names' => array(
                'userId' => '用户',
                'content' => '消息内容',
                'appMsgId' => '图文消息编号'
            )
        ));

        if (!$validator->isValid()) {
            return $this->err($validator->getFirstMessage());
        }

        // 2. 获取当前用户,平台对象的微信账号,及API服务对象
        $user = wei()->user()->findOneById($req['userId']);
        switch (true) {
            case $user['wechatOpenId'] :
                $account = wei()->wechatAccount->getCurrentAccount();
                break;

            case $user['qqOpenId'] :
                $account = wei()->qqAccount->getCurrentAccount();
                break;

            default:
                return $this->err('用户不是来自第三方平台,无法发送信息');
        }
        $api = $account->createApiService();

        // 3. 调用发送接口,记录消息,并返回结果
        $result = $api->sendByUser($user, $type, $req);

        // 发送成功则记录消息
        if ($result) {
            wei()->message()->saveData([
                'userId' => $user['id'],
                'msgType' => $type,
                'platformId' => $account::PLATFORM_ID,
                'content' => $req['content'],
                'replyMessageId' => (int)$req['replyMessageId'],
                'createTimestamp' => time(),
            ]);
        }

        return $this->response->json($api->getResult());
    }

    public function updateAction($req)
    {
        $message = wei()->message()->findOneById($req['id']);
        $message->save($req);

        return $this->suc();
    }
}
