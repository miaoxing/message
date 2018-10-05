<?php $view->layout() ?>

<!-- /.page-header -->
<div class="page-header">
  <h1>
    消息管理
  </h1>
</div>

<div class="row">
  <div class="col-xs-12 col-sm-10 col-sm-offset-1">
    <!-- PAGE CONTENT BEGINS -->
    <div class="table-responsive">
      <?php require $view->getFile('@message/admin/message/list.php') ?>
    </div>
    <!-- /.table-responsive -->
    <!-- PAGE CONTENT ENDS -->
  </div>
  <!-- /col -->
</div>

<!-- /row -->
<?php require $view->getFile('@wechat/wechat/media/tpls.php') ?>

<?= $block->js() ?>
<script>
  require(['plugins/message/js/admin/list'], function (list) {
    list.init({
      params: {
        // 列表页不加载公众号消息
        source: 1
      }
    });
  });
</script>
<?= $block->end() ?>
