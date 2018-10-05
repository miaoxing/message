<?= $block->css() ?>
<link rel="stylesheet" href="<?= $asset('plugins/message/css/admin/list.css') ?>"/>
<?= $block->end() ?>

<div class="well form-well col-sm-12">
  <form class="form-inline" id="search-form" role="form">
    <select class="form-control" name="group">
      <option value="all" selected>全部消息</option>
      <option value="today">今天消息</option>
      <option value="starred">星标消息</option>
    </select>

    <div class="form-group">
      <input type="text" class="form-control" name="search" placeholder="请输入消息内容查询">
    </div>

    <div class="pull-right">
      <div class="checkbox">
        <label>
          <input type="hidden" value="1" name="fromKeyword">
          <input type="checkbox" class="ace" name="fromKeyword" value="0" checked>
          <span class="lbl">&nbsp;隐藏关键词消息</span>
        </label>
      </div>
      &nbsp;&nbsp;
      <a id="export-csv" class="btn btn-white" href="javascript:void(0);">导出</a>
    </div>
  </form>
</div>

<a class="new-message-tip alert alert-warning text-center display-none" href="javascript:">
  <span class="new-message-number">1</span>条新消息，点击查看
</a>

<table id="message-table" class="table table-hover message-table">
</table>


<script id="user-head-img-tpl" type="text/html">
  <% if (source == '1') { %>
  <a class="pull-left user-popover" data-id="<%= user.id %>" target="_blank"
    href="<%= $.url('admin/message/user', {userId: user.id}) %>">
    <img class="media-object" src="<%= user.headImg %>">
  </a>
  <% } else { %>
  <img class="media-object" src="<%= user.headImg %>">
  <% } %>
</script>

<script id="user-message-tpl" type="text/html">
  <div class="media user-media">
    <div class="media-body">
      <h4 class="media-heading">
        <% if (source == '1') { %>
        <a href="<%= $.url('admin/message/user', {userId: user.id}) %>"
          target="_blank"><%= user.nickName || '&nbsp;' %></a>
        <% } else { %>
        <%= user.nickName || '&nbsp;' %>
        <% } %>
      </h4>
      <%== content %>
      <div class="quick-reply-container display-none" id="quick-reply-<%= id %>">
        <hr>
        <h6>快速回复:</h6>

        <form class="reply-form" method="post" role="form" action="<%= $.url('admin/message/send') %>">
          <div>
            <textarea name="content" rows="2"></textarea>
            <input type="hidden" name="userId" value="<%= userId %>">
            <input type="hidden" name="platformId" value="<%= platformId %>">
            <input type="hidden" name="replyMessageId" value="<%= id %>">
          </div>
          <div>
            <button class="btn btn-primary btn-sm" type="submit" data-loading-text="发送中...">发送</button>
            <a class="hide-replay-box btn btn-link btn-sm" href="javascript:" data-id="<%= id %>">收起</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</script>

<script id="table-actions" type="text/html">
  <div class="media-actions text-center bigger-130">
    <a class="star-link" href="javascript:" title="<%= starTitle %>" data-id="<%= id %>">
      <i class="fa fa-star <%= starClass %>"></i>
    </a>
    <a class="quick-reply-link" href="javascript:" title="快速回复">
      <i class="fa fa-reply"></i>
    </a>
  </div>
</script>

<?php require $view->getFile('@user/admin/user/richInfo.php') ?>
