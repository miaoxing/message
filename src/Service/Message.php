<?php

namespace Miaoxing\Message\Service;

class Message extends \miaoxing\plugin\BaseModel
{
    protected $types = [
        'text' => '文本',
        'article' => '图文',
        'image' => '图片',
        'event' => '事件',
    ];

    /**
     * 保存之前统一换行符
     */
    public function beforeSave()
    {
        $this['content'] = str_replace("\r\n", "\n", $this['content']);
    }

    public function afterFind()
    {
        switch ($this['msgType']) {
            case 'text' :
                $this['content'] = nl2br($this['content']);
                break;

            default:
                $this['content'] = (array)json_decode($this['content'], true);
        }
    }

    /**
     * 获取未读消息数量
     *
     * @return int
     */
    public function getUnreadMessageNumber()
    {
        // 大于最后阅读时间, 来自用户, 不是触发关键字的
        return wei()->message()
            ->where('createTimestamp > ?', $this->getLastReadMessageTime())
            ->andWhere('source = 1')
            ->andWhere('fromKeyword = 0')
            ->count();
    }

    /**
     * 设置最后阅读消息时间戳
     *
     * @return $this
     */
    public function setLastReadMessageTime()
    {
        $appId = wei()->app->getId();
        $this->cache->set('lastReadMessageTime' . $appId, time());
        return $this;
    }

    /**
     * 获取最后阅读消息的时间戳
     *
     * @return int
     */
    public function getLastReadMessageTime()
    {
        $appId = wei()->app->getId();
        return (string)$this->cache->get('lastReadMessageTime' . $appId);
    }
}
