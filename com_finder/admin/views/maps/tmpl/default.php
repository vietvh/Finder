<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_finder
 *
 * @copyright   Copyright (C) 2005 - 2011 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;

$lang = &JFactory::getLanguage();
JText::script('COM_FINDER_MAPS_CONFIRM_DELETE_PROMPT');
?>

<script type="text/javascript">
Joomla.submitbutton = function(pressbutton) {
	if (pressbutton == 'map.delete') {
		if (confirm(Joomla.JText._('COM_FINDER_MAPS_CONFIRM_DELETE_PROMPT'))) {
			Joomla.submitform(pressbutton);
		} else {
			return false;
		}
	}
	Joomla.submitform(pressbutton);
}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_finder&view=maps');?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="filter-search fltlft">
			<label class="filter-search-lbl" for="filter_search"><?php echo JText::sprintf('COM_FINDER_SEARCH_LABEL', JText::_('COM_FINDER_MAPS')); ?></label>
			<input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_FINDER_FILTER_SEARCH_DESCRIPTION'); ?>" />
			<button type="submit" class="btn"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
			<button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
		<div class="filter-select fltrt">
			<select name="filter_branch" class="inputbox" onchange="this.form.submit()" id="filter_branch">
				<?php echo JHtml::_('select.options', JHtml::_('finder.mapslist'), 'value', 'text', $this->state->get('filter.branch'), true);?>
			</select>
			<select name="filter_state" class="inputbox" onchange="this.form.submit()">
				<option value=""><?php echo JText::_('COM_FINDER_INDEX_FILTER_BY_STATE');?></option>
				<?php echo JHtml::_('select.options', JHtml::_('finder.statelist'), 'value', 'text', $this->state->get('filter.state'), true);?>
			</select>
		</div>
	</fieldset>
	<div class="clr"> </div>

	<table class="adminlist" style="clear: both;">
		<thead>
			<tr>
				<th width="1%">
					<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
				</th>
				<th class="nowrap">
					<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?>
				</th>
				<th class="nowrap" width="10%">
					<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php if (count($this->items) == 0): ?>
			<tr class="row0">
				<td class="center" colspan="5">
					<?php echo JText::_('COM_FINDER_MAPS_NO_CONTENT'); ?>
				</td>
			</tr>
			<?php endif; ?>
			<?php if ($this->state->get('filter.branch') != 1) : ?>
			<tr class="row0">
				<td colspan="5" class="center">
					<a href="#" onclick="document.id('filter_branch').value='1';document.adminForm.submit();">
						<?php echo JText::_('COM_FINDER_MAPS_RETURN_TO_BRANCHES'); ?></a>
				</td>
			</tr>
			<?php endif; ?>

			<?php $n = 1; $o = 0; ?>
			<?php $canChange	= JFactory::getUser()->authorise('core.manage',	'com_finder'); ?>
			<?php foreach ($this->items as $row): ?>

			<tr class="row<?php echo $n % 2; ?>">
				<td class="center">
					<?php echo JHtml::_('grid.id', $n, $row->id); ?>
				</td>
				<td>
					<?php
						$key = 'COM_FINDER_TYPE_S_'.strtoupper(str_replace(' ', '_', $row->title));
						$title = $lang->hasKey($key) ? JText::_($key) : $row->title;
					?>
					<?php if ($this->state->get('filter.branch') == 1 && $row->num_children) : ?>
						<a href="#" onclick="document.id('filter_branch').value='<?php echo (int) $row->id;?>';document.adminForm.submit();" title="<?php echo JText::_('COM_FINDER_MAPS_BRANCH_LINK'); ?>">
							<?php echo $this->escape($title); ?></a>
					<?php else: ?>
						<?php echo $this->escape($title); ?>
					<?php endif; ?>
					<?php if ($row->num_children > 0) : ?>
						<small>(<?php echo $row->num_children; ?>)</small>
					<?php elseif ($row->num_nodes > 0) : ?>
						<small>(<?php echo $row->num_nodes; ?>)</small>
					<?php endif; ?>
				</td>
				<td class="center nowrap">
					<?php echo JHtml::_('jgrid.published', $row->state, $n, 'maps.', $canChange, 'cb'); ?>
				</td>
			</tr>

			<?php $n++; ?>
			<?php endforeach; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="9" class="nowrap">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
	</table>

	<input type="hidden" name="task" value="display" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->state->get('list.ordering') ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->state->get('list.direction') ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
