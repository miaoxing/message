define([
  'assets/time',
  'template',
  'comps/jquery_lazyload/jquery.lazyload.min',
  'plugins/admin/js/data-table',
  'jquery-unparam',
  'form'
], function (time, template) {
  var self = {};

  // 消息表格对象
  self.table = null;

  // 最后信息的ID
  self.lastId = 0;

  // setTimeout返回的ID
  self.timeoutId = 0;

  // 每隔多少秒去后台拉取消息
  self.delay = 10000;

  // 消息是否已经加载完
  self.loaded = false;

  // 消息列表的URL地址
  self.baseUrl = $.queryUrl('admin/message?_format=json');

  // 附加到消息列表的参数
  self.params = {};

  // 初始化
  self.init = function (options) {
    $.extend(self, options);

    self.initTable();
    self.bindEvents();
  };

  self.initTable = function () {
    // 合并新的地址
    self.baseUrl = $.appendUrl(self.baseUrl, self.params);

    self.table = $('#message-table').dataTable({
      displayLength: 20,
      ajax: {
        url: $.appendUrl(self.baseUrl, $('#search-form').serialize())
      },
      columns: [
        {
          data: 'user',
          sClass: 'head-img',
          render: function (data, type, full) {
            return template.render('user-head-img-tpl', full);
          }
        },
        {
          data: 'content',
          render: function (data, type, full) {
            switch (full.msgType) {
              case 'text':
                break;
              case 'news':
                if (typeof full.content.Articles !== 'undefined') {
                  // todo 微信的才需要转换
                  $.each(full.content.Articles.item, function(key, value) {
                    full.content.Articles.item[key].PicUrl = $.url('wechat/media/imageProxy', {
                      url: value.PicUrl
                    });
                  });
                  full.content = template.render('media-news', full.content);
                }
                break;
              case 'event':
                if (!full.content.Event) {
                  break;
                }
                switch (full.content.Event.toLowerCase()) {
                  case 'subscribe' :
                    full.content = '(用户订阅了您)';
                    break;
                  case 'unsubscribe' :
                    full.content = '(用户取消订阅了您)';
                    break;
                  case 'click':
                    full.content = '(用户点击了菜单:' + full.content.EventKey + ')';
                    break;
                  default:
                    full.content = '(即将支持该消息类型)';
                }
                break;
              case 'image':
                if (full.content.PicUrl) {
                  full.content = '<img style="max-height: 400px;" src="' + full.content.PicUrl + '" />';
                }
                break;
              default:
                full.content = '(即将支持该消息类型)';
                break;
            }
            return template.render('user-message-tpl', full);
          }
        },
        {
          data: 'createTimestamp',
          sClass: 'text-right create-time',
          render: function (data) {
            return '<time title="' + data + '">' + time.timeFormat(data) + '</time>';
          }
        },
        {
          data: 'id',
          sClass: 'actions',
          render: function (data, type, full) {
            if (full.starred === '1') {
              full.starTitle = '取消收藏';
              full.starClass = 'light-orange';
            } else {
              full.starTitle = '收藏此消息';
              full.starClass = '';
            }
            return template.render('table-actions', full);
          }
        }
      ],
      fnCreatedRow: function (row, data) {
        // 取最大的编号供查询使用
        var id = parseInt(data.id, 10);
        if (id > self.lastId) {
          self.lastId = id;
        }

        // 新消息动画效果展示
        if (self.loaded) {
          $(row).hide().prependTo(this).fadeIn();
        }
      },
      drawCallback: function () {
        self.restart();
      }
    });
  };

  self.render = function () {
    self.loaded = true;
    $
      .ajax({
        cache: false,
        url: $.appendUrl(self.baseUrl, $('#search-form').serialize()),
        dataType: 'json',
        data: {
          lastId: self.lastId
        },
        success: function () {
          // 留空屏蔽默认提示信息
        }
      })
      .done(function (result) {
        if (result.data.length !== 0) {
          // 如果是第一页,直接渲染,否则,提示新消息
          if (self.table.fnSettings()._iDisplayStart === 0) {
            self.table.fnAddData(result.data.reverse(), false);
          } else {
            $('.new-message-number').text(result.data.length);
            $('.new-message-tip').css('display', 'block');
          }
        }
      })
      .always(function () {
        self.restart();
      });
  };

  self.restart = function () {
    clearTimeout(self.timeoutId);
    self.timeoutId = setTimeout(self.render, self.delay);
  };

  // 绑定所有事件
  self.bindEvents = function () {
    // 鼠标移到行上,显示回复和收藏图标
    self.table.on('mouseover', 'tr', function () {
      $('.media-actions i', this).css('visibility', 'visible');
    });

    // 鼠标移出行上,隐藏回复和收藏图标
    self.table.on('mouseout', 'tr', function () {
      $('.media-actions i:not(.light-orange)', this).css('visibility', 'hidden');
    });

    // 收藏/取消收藏消息
    self.table.on('click', '.star-link', function () {
      var icon = $(this).find('i.fa-star');
      $.ajax({
        url: $.url('admin/message/update'),
        dataType: 'json',
        data: {
          id: $(this).data('id'),
          starred: Number(!icon.hasClass('light-orange'))
        },
        success: function (result) {
          $.msg(result);
          if (result.code > 0) {
            icon.toggleClass('light-orange');
            if (icon.hasClass('light-orange')) {
              icon.attr('title', '取消收藏');
            } else {
              icon.attr('title', '收藏此消息');
            }
          }
        }
      });
    });

    // 显示/隐藏快速回复
    self.table.on('click', '.quick-reply-link', function () {
      $(this).parents('tr:first')
        .find('.quick-reply-container')
        .slideToggle()
        .find('textarea')
        .focus();
    });

    // 隐藏快速回复
    self.table.on('click', '.hide-replay-box', function () {
      $(this).parents('.quick-reply-container:first').slideUp();
    });

    // 提交回复表单
    self.table.on('submit', '.reply-form', function (event) {
      var form = $(this);
      form.ajaxSubmit({
        dataType: 'json',
        beforeSend: function () {
          form.find(':submit').button('loading');
        },
        complete: function () {
          form.find(':submit').button('reset');
        },
        success: function (result) {
          $.msg(result, function () {
            if (result.code > 0) {
              form.find('textarea').val('');
              self.render();
              self.restart();
            }
          });
        }
      });

      event.preventDefault();
    });

    // 图片延迟加载
    $('img.lazy').lazyload({
      container: self.table
    });

    // 点击新消息,跳转回第一页
    $('.new-message-tip').click(function () {
      var setting = self.table.fnSettings();
      setting._iDisplayStart = 0;
      self.table.fnDraw(setting);

      $(this).hide();
    });

    // 导出消息到EXCEL
    $('#export-csv').click(function () {
      var url = $.appendUrl(self.table.fnSettings().ajax.url, {
        page: 1,
        rows: 99999,
        _format: 'csv'
      });
      window.location = url;
    });

    // 搜索信息
    $('#search-form').update(function () {
      self.table.reload($(this).serialize(), false);
    });
  };

  return self;
});
