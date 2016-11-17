<?php if (!empty($collections) || $filtering) { ?>
<div class="panel panel-default">
  <div class="panel-heading clearfix">
    <div class="btn-group pull-left">
      <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
        <?php echo $this->text('With selected'); ?> <span class="caret"></span>
      </button>
      <ul class="dropdown-menu">
        <?php if ($this->access('collection_edit')) { ?>
        <li>
          <a data-action="status" data-action-value="1" href="#">
            <?php echo $this->text('Status'); ?>: <?php echo $this->text('Enabled'); ?>
          </a>
        </li>
        <li>
          <a data-action="status" data-action-value="0" href="#">
            <?php echo $this->text('Status'); ?>: <?php echo $this->text('Disabled'); ?>
          </a>
        </li>
        <?php } ?>
        <?php if ($this->access('collection_delete')) { ?>
        <li>
          <a data-action="delete" href="#">
            <?php echo $this->text('Delete'); ?>
          </a>
        </li>
        <?php } ?>
      </ul>
    </div>
    <?php if ($this->access('collection_add')) { ?>
    <div class="btn-toolbar pull-right">
      <a class="btn btn-default" href="<?php echo $this->url('admin/content/collection/add'); ?>">
        <i class="fa fa-plus"></i> <?php echo $this->text('Add'); ?>
      </a>
    </div>
    <?php } ?>
  </div>
  <div class="panel-body">
    <table class="table table-responsive table-striped collections">
      <thead>
        <tr>
          <th><input type="checkbox" id="select-all" value="1"></th>
          <th><a href="<?php echo $sort_collection_id; ?>"><?php echo $this->text('ID'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_title; ?>"><?php echo $this->text('Title'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_type; ?>"><?php echo $this->text('Type'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_store_id; ?>"><?php echo $this->text('Store'); ?> <i class="fa fa-sort"></i></a></th>
          <th><a href="<?php echo $sort_status; ?>"><?php echo $this->text('Status'); ?> <i class="fa fa-sort"></i></a></th>
          <th></th>
        </tr>
        <tr class="filters active">
          <th></th>
          <th></th>
          <th>
            <input class="form-control" name="title" value="<?php echo $filter_title; ?>" placeholder="<?php echo $this->text('Any'); ?>">
          </th>
          <th>
            <select class="form-control" name="type">
              <option value=""<?php echo ($filter_type === '') ? ' selected' : ''; ?>>
              <?php echo $this->text('Any'); ?>
              </option>
              <?php foreach ($handlers as $handler_id => $handler) { ?>
              <?php if ($filter_type === $handler_id) { ?>
              <option value="<?php echo $this->escape($handler_id); ?>" selected><?php echo $this->escape($handler['title']); ?></option>
              <?php } else { ?>
              <option value="<?php echo $this->escape($handler_id); ?>"><?php echo $this->escape($handler['title']); ?></option>
              <?php } ?>
              <?php } ?>
            </select>
          </th>
          <th>
            <select class="form-control" name="store_id">
              <option value=""<?php echo (!$filter_store_id) ? ' selected' : ''; ?>><?php echo $this->text('Any'); ?></option>
              <?php foreach ($stores as $store_id => $store_name) { ?>
              <option value="<?php echo $store_id; ?>"<?php echo ($filter_store_id == $store_id) ? ' selected' : ''; ?>><?php echo $this->escape($store_name); ?></option>
              <?php } ?>
            </select>
          </th>
          <th>
            <select class="form-control" name="status">
              <option value="any">
              <?php echo $this->text('Any'); ?>
              </option>
              <option value="1"<?php echo ($filter_status === '1') ? ' selected' : ''; ?>>
              <?php echo $this->text('Enabled'); ?>
              </option>
              <option value="0"<?php echo ($filter_status === '0') ? ' selected' : ''; ?>>
              <?php echo $this->text('Disabled'); ?>
              </option>
            </select>
          </th>
          <th>
            <button type="button" class="btn btn-default clear-filter" title="<?php echo $this->text('Reset filter'); ?>">
              <i class="fa fa-refresh"></i>
            </button>
            <button type="button" class="btn btn-default filter" title="<?php echo $this->text('Filter'); ?>">
              <i class="fa fa-search"></i>
            </button>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($collections) && $filtering) { ?>
        <tr>
          <td colspan="7">
            <?php echo $this->text('No results'); ?>
            <a class="clear-filter" href="#"><?php echo $this->text('Reset'); ?></a>
          </td>
        </tr>
        <?php } else { ?>
        <?php foreach ($collections as $id => $collection) { ?>
        <tr>
          <td class="middle">
            <input type="checkbox" class="select-all" name="selected[]" value="<?php echo $id; ?>">
          </td>
          <td class="middle"><?php echo $this->escape($id); ?></td>
          <td class="middle"><?php echo $this->escape($collection['title']); ?></td>
          <td class="middle">
            <?php if (isset($handlers[$collection['type']]['title'])) { ?>
            <?php echo $this->escape($handlers[$collection['type']]['title']); ?>
            <?php } else { ?>
            <span class="text-danger"><?php echo $this->text('Unknown'); ?></span>
            <?php } ?>
          </td>
          <td class="middle">
            <?php if (isset($stores[$collection['store_id']])) { ?>
            <?php echo $this->escape($stores[$collection['store_id']]); ?>
            <?php } else { ?>
            <span class="text-danger"><?php echo $this->text('Unknown'); ?></span>
            <?php } ?>
          </td>
          <td class="middle">
            <?php if (empty($collection['status'])) { ?>
            <i class="fa fa-square-o"></i>
            <?php } else { ?>
            <i class="fa fa-check-square-o"></i>
            <?php } ?>
          </td>
          <td class="middle">
            <ul class="list-inline">
              <?php if ($this->access('collection_edit')) { ?>
              <li>
                <a href="<?php echo $this->url("admin/content/collection/edit/$id"); ?>">
                  <?php echo strtolower($this->text('Edit')); ?>
                </a>
              </li>
              <?php } ?>
              <?php if ($this->access('collection_item')) { ?>
              <li>
                <a href="<?php echo $this->url("admin/content/collection-item/$id"); ?>">
                  <?php echo strtolower($this->text('Items')); ?>
                </a>
              </li>
              <?php } ?>
            </ul>
          </td>
        </tr>
        <?php } ?>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)) { ?>
  <div class="panel-footer text-right">
    <?php echo $pager; ?>
  </div>
  <?php } ?>
</div>
<?php } else { ?>
<div class="row">
  <div class="col-md-12">
    <?php echo $this->text('You have no collections'); ?>
    <?php if ($this->access('collection_add')) { ?>
    <a href="<?php echo $this->url('admin/content/collection/add'); ?>">
      <?php echo $this->text('Add'); ?>
    </a>
    <?php } ?>
  </div>
</div>
<?php } ?>