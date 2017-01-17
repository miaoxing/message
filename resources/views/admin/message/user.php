<?php $view->layout() ?>

<div class="page-header">
  <h1>
    实时消息
    <small>
      <i class="fa fa-angle-double-right"></i>
      与"<?= $e($user['nickName']) ?>"聊天
    </small>
  </h1>
</div>

<div class="row">
  <div class="col-xs-12 col-sm-10 col-sm-offset-1">
    <?php require $this->getFile('message:admin/message/chatbox.php') ?>
    <hr>
    <?php require $this->getFile('message:admin/message/list.php') ?>
  </div>
</div>

<?= $block('js') ?>
<script>
  require(['plugins/message/js/admin/list'], function (list) {
    // 列表页显示当前用户所有消息
    list.init({
      params: {
        userId: <?= $user['id'] ?>,
        group: 'all'
      }
    });
  });
</script>
<?= $block->end() ?>
