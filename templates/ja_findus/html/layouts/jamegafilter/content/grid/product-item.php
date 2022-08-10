<?php
/*
 * ------------------------------------------------------------------------
 * JA Filter Plugin - Docman
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2016 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - GNU/GPL, http://www.gnu.org/licenses/gpl.html
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites: http://www.joomlart.com - http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */

defined('_JEXEC') or die;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use T4\Helper\J3J4;

?>
{#data}
<div class="item product product-item">
  <div data-container="product-grid" class="product-item-info {?thumbnail}{:else} no-image {/thumbnail}">
    <div class="product-item-details">
      {@info:data}
      <div class="item-field {._class} {render_class} {label_render_class}">
        {?.key}<div class="item-field-label {label_render_class}">{.key}</div>{/.key}
          {@select key=._class}
            {@eq value="name"}
            <h4 class="product-item-name">
              <a href="{url}" class="product-item-link">
                <span class="k-icon-document-{icon|s}"> </span>
                {name|s}
              </a>
            </h4>
            {/eq}
            {@eq value="thumb"}
              {?thumbnail}
                <a tabindex="-1" class="product-item-photo" href="{url}">
                    <span class="product-image-container">
                      <img alt="{name|s}" src="{thumbnail}" class="product-image-photo">
                    </span>
                </a>
              {/thumbnail}
            {/eq}
            {@eq value="desc"}
              {desc|s}
            {/eq}
            {@eq value="ct1"}
              <div class="{.value}">{val_class|s}</div>
            {/eq}
            {@eq value="ct14"}
<!--
              {?.val_business_time}<div class="{.val_class} stt-open"><?php echo JText::_('TPL_OPEN_NOW'); ?></div>{:else} <div class="{.val_class} stt-closed"><?php echo Jtext::_('TPL_CLOSED'); ?></div> {/val_business_time}
-->
            {/eq}
            {@none}
              <div class="{.val_class}">{.value|s}</div>
            {/none}
          {/select}
      </div>
      {/info}
    </div>
    <div class="product-item-actions">
      <a class="btn btn-default" href="{url}"><?php echo JText::_('COM_JAMEGAFILTER_VIEW_DETAIL'); ?></a>
    </div>
  </div>
</div>
{/data}
