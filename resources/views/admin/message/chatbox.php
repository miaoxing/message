<ul id="editor-tabs" class="nav nav-tabs padding-12 tab-color-blue background-blue">
  <li class="active">
    <a id="text-tab" data-toggle="tab" href="#text"><i class="fa fa-pencil"></i>&nbsp;文字</a>
  </li>
  <!--
  <li>
      <a data-toggle="tab" href="#news"><i class="fa fa-list-alt"></i>&nbsp;图文消息</a>
  </li>
  -->
</ul>
<div class="tab-content">
  <div id="text" class="tab-pane in active">
    <div class="row">
      <div class="col-lg-12">
        <form id="message-form" class="form-horizontal" method="post" role="form"
          action="<?= $url('admin/message/send') ?>">
          <div class="form-group">
            <div class="col-lg-12">
              <textarea id="content" class="form-control" name="content"
                rows="4"><?= $wei->e($req['message']) ?></textarea>
            </div>
          </div>
          <button id="submit-btn" class="btn btn-info" type="submit" data-loading-text="处理中...">
            <i class="fa fa-check bigger-110"></i>发送
          </button>
          <input type="hidden" name="platformId" value="<?= wei()->wechatAccount->getCurrentAccount()->get('id') ?>">
          <input type="hidden" name="type" value="text">
          <input type="hidden" id="user-id" name="userId" value="<?= $user['id'] ?>">
        </form>
      </div>
    </div>
  </div>
  <div id="news" class="tab-pane">
    <div class="text-right clearfix">
      <ul class="news-paginator pagination"></ul>
      <a href="https://mp.weixin.qq.com" class="pull-right" rel="noreferrer" target="_blank">
        <i class="icon-external-link"></i>&nbsp;前往公众平台新建图文消息
      </a>
    </div>
    <ul id="news-list" class="ace-thumbnails">
    </ul>
    <div class="text-right">
      <ul class="news-paginator pagination">
      </ul>
    </div>
  </div>
</div>

<?php require $view->getFile('wechat:wechat/media/tpls.php') ?>

<?= $block->js() ?>
<script>
  require([
    'form',
    'assets/bootstrapPaginator',
    'plugins/message/js/admin/list'
  ], function (form, bp, list) {
    $('#message-form').ajaxForm({
      dataType: 'json',
      beforeSend: function () {
        $('#submit-btn').button('loading');
      },
      complete: function () {
        $('#submit-btn').button('reset');
      },
      success: function (result) {
        $.msg(result);
        if (result.code < 0) {
          $.err(result.message);
        } else {
          list.render();
          $('#content').val('');
          $.suc('发送成功!');
        }
      }
    });

    // 切换选项卡,加载不同的内容
    $('#editor-tabs').on('shown.bs.tab', function (e) {
      var id = $(e.target).attr('href');
      if (id == '#news') {
        renderNewsList(1);
      }
    });

    var $tip;
    var articles = [];
    var newsList = $('#news-list');
    var paginator = $('.news-paginator');

    function renderNewsList(page) {
      return;
      $.ajax({
        url: $.url('wechat/media/newsList'),
        dataType: 'json',
        data: {
          page: page
        },
        beforeSend: function () {
          $.info('加载中...', 10000);
        },
        success: function (data) {
          $.tips.hideAll();

          // 清空已有的数据并逐条渲染
          newsList.empty();
          for (var i in data.item) {
            renderSingleNews(data.item[i]);
          }

          paginator.bootstrapPaginator({
            currentPage: 1,
            totalPages: data.file_cnt / 10,
            onPageClicked: function (e, originalEvent, type, page) {
              e.stopImmediatePropagation();
              renderNewsList(page);
            }
          });

          $('img.lazy', newsList).lazyload();

          if (newsList.data('masonry')) {
            newsList.masonry('destroy');
          }
          newsList.masonry({
            itemSelector: '.news-item'
          });
        }
      })
    }

    function renderSingleNews(item) {
      // 预存到article变量中,用于发送到后台
      articles[item.app_id] = appMsgToArticle(item);

      // 更改图片地址
      var list = $.extend(true, {}, articles[item.app_id]);

      // 更新文章中真实的图片地址,用于存储到后台
      for (var i in articles[item.app_id].Articles.item) {
        articles[item.app_id].Articles.item[i].PicUrl =
          decodeURIComponent(getUrlParam(articles[item.app_id].Articles.item[i].PicUrl, 'url'));
      }

      var newsItem = $(template.render('media-news', list));
      newsList.append(newsItem);

      // 点击直接发送
      newsItem.find('a.send').click(function () {
        var link = $(this);
        var id = link.data('id');
        $.ajax({
          url: $.url('admin/message/send'),
          data: {
            type: 'news',
            userId: $('#user-id').val(),
            appMsgId: id,
            content: JSON.stringify(articles[id])
          },
          dataType: 'json',
          beforeSend: function () {
            $.info('加载中...', 10000);
          },
          success: function (result) {
            $.tips.hideAll();
            if ($.isMassMessage) {
              massSuccess(result);
            } else {
              if (result.code > 0) {
                $.suc(result.message);
                $('#text-tab').tab('show');
                $.messageFlow.render().restart();
              } else {
                $.err(result.message);
              }
            }
          }
        });
      });
    }

    function appMsgToArticle(item) {
      var msg = {
        ArticleCount: item.multi_item.length,
        Articles: {
          item: []
        },
        ToUserName: '',
        time: item.create_time,
        appId: item.app_id
      };
      for (var i in item.multi_item) {
        msg.Articles.item[i] = {
          Title: item.multi_item[i].title,
          Description: item.multi_item[i].digest,
          PicUrl: item.multi_item[i].cover,
          Url: item.multi_item[i].content_url
        }
      }
      return msg;
    }

    function getUrlParam(url, name) {
      return decodeURI(
        (RegExp(name + '=' + '(.+?)(&|$)').exec(url) || [, null])[1]
      );
    }
  });
</script>
<?= $block->end() ?>
