<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_category
 *
 * @copyright   Copyright (C) 2005 - 2021 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Language\Text;
require_once(__DIR__.'/../../helper.php');

$modStyle = $params->get('list-style') ? $params->get('list-style') : '1';
$modShow = $params->get('show-link');
$modCat = $params->get('title-category');
$modMenu = $params->get('link-category');

?>

<div class="view-listings-wrap list-course style-<?php echo $modStyle ;?>">
	<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 v-gutters">
		<?php foreach ($list as $item) : ?>
			<div class="col">
				<div class="item">
					<div class="view-listing">
						<!-- Item image -->
						<?php
						$offset = "";
						$item->event = new stdClass;
						$results = JFactory::getApplication()->triggerEvent('onContentAfterDisplay', array('com_content.article', &$item, &$item->params, $offset));
						$item->event->afterDisplayContent = trim(implode("\n", $results));
						// Get Extrafields
						$customs      = JATemplateHelper::getCustomFields($item->id, 'article');
						$extrafields = new JRegistry($item->attribs);
						$ltAddress = empty($customs['address']) ? '' : implode(",", $customs['address']);
						$businessTime = empty($customs['business-time']) ? '' : explode("-", implode(", ", $customs['business-time']));
						$ltOpen = empty($businessTime) ? '' : trim($businessTime[0]);
						$ltClose = empty($businessTime) ? '' : trim($businessTime[1]);
						$ltStatus = JATemplateHelper::OpenClosedTime($ltOpen, $ltClose);
						$ltBusOffline = $extrafields->get('offline');
						$ltBusHours = $extrafields->get('business-hours');
						$ltClosedOnPH = $extrafields->get('public-holiday');

						$item->link 	= Route::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));

						$images = "";
						if (isset($item->images)) {
							$images = json_decode($item->images);
						}

						$imgexists = (isset($images->image_intro) and !empty($images->image_intro)) || (isset($images->image_fulltext) and !empty($images->image_fulltext));
						
						if ($imgexists) {			
							$images->image_intro = $images->image_intro?$images->image_intro:$images->image_fulltext;
						?>

						<div class="image-wrap">
							<a class="item-image" href="<?php echo $item->link; ?>" title="<?php echo $item->title; ?>">
								<img src="<?php echo htmlspecialchars($images->image_intro); ?>" alt="<?php echo $item->title; ?>" />
							</a>

							<?php if(!empty($customs['listing-type'])): ?>
				        <div class="listing-type">
				          <span class="<?php echo implode(",", $customs['listing-type']) ;?>"></span>
				        </div>
				      <?php endif ;?>
						</div>
						<?php } ?>
						<!-- // Item image -->
						
						<div class="intro-wrap">
							<div class="top-info">
								<?php if ($params->get('link_titles') == 1) : ?>
									<h6 class="item-title"><a class="link-dark <?php echo $item->active; ?>" href="<?php echo $item->link; ?>"><?php echo $item->title; ?></a></h6>
								<?php else : ?>
									<h6><?php echo $item->title; ?></h6>
								<?php endif; ?>

<!--
								<?php if($ltOpen || $ltClose) :?>
							          <div class="fs-xs mb-1 stt-time stt-<?php echo strtolower(preg_replace('/\s*/', '', $ltStatus)) ;?>"><?php echo $ltStatus ;?></div>
							        <?php endif ;?>
-->

                                                        <?php
							$hstatus = '';
							if ($ltBusOffline == 0):
								if($ltBusHours):
                                                                        $d = new DateTime();
                                                                        $day = $d->format('l');
                                                                        $daykey = strtolower($d->format('l'));
                                                                        $hourskey = strtolower(substr($day, 0, 3)).'_hours';
                                                                        $hours = $d->format('H:i');
                                                                        $today = $ltBusHours->{$daykey}.": ";
                                                                        $today_hours = $ltBusHours->{$hourskey};
                                                                        $bh = explode("-", $today_hours);
									$hstatus = JATemplateHelper::isOpen($bh[0], $bh[1], $ltClosedOnPH);
								endif;
							else:
								$hstatus = Text::_('TPL_CLOSED_DOWN');;
							endif;
                                                        ?>
							<span class="stt-time stt-<?php echo strtolower(substr($hstatus,0,strpos(str_pad($hstatus, 7, " ")," "))) ;?>"><?php echo $hstatus ;?></span>


				        <?php if($ltAddress) :?>
				          <div class="location fs-xs"><?php echo $ltAddress ;?></div>
				        <?php endif ;?>

				        <?php if ($item->displayDate || $item->displayHits || $params->get('show_author')) : ?>
								<div class="article-aside">
									<div class="article-info">
										<?php if ($item->displayDate) : ?>
											<span class="item-date">
												<?php echo $item->displayDate; ?>
											</span>
										<?php endif; ?>

										<?php if ($item->displayHits) : ?>
											<span class="mod-articles-category-hits">
												(<?php echo $item->displayHits; ?>)
											</span>
										<?php endif; ?>

										<?php if ($params->get('show_author')) : ?>
											<span class="mod-articles-category-writtenby">
												<?php echo $item->displayAuthorName; ?>
											</span>
										<?php endif; ?>
									</div>
								</div>
								<?php endif; ?>

								<?php if ($params->get('show_introtext')) : ?>
									<p class="item-introtext">
										<?php echo $item->displayIntrotext; ?>
									</p>
								<?php endif; ?>

								<?php if ($params->get('show_tags', 0) && $item->tags->itemTags) : ?>
									<div class="mod-articles-category-tags">
										<?php echo LayoutHelper::render('joomla.content.tags', $item->tags->itemTags); ?>
									</div>
								<?php endif; ?>

								<?php if ($params->get('show_readmore')) : ?>
									<p class="item-readmore">
										<a class="mod-articles-category-title <?php echo $item->active; ?>" href="<?php echo $item->link; ?>">
											<?php if ($item->params->get('access-view') == false) : ?>
												<?php echo JText::_('MOD_ARTICLES_CATEGORY_REGISTER_TO_READ_MORE'); ?>
											<?php elseif ($readmore = $item->alternative_readmore) : ?>
												<?php echo $readmore; ?>
												<?php echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
											<?php elseif ($params->get('show_readmore_title', 0) == 0) : ?>
												<?php echo JText::sprintf('MOD_ARTICLES_CATEGORY_READ_MORE_TITLE'); ?>
											<?php else : ?>
												<?php echo JText::_('MOD_ARTICLES_CATEGORY_READ_MORE'); ?>
												<?php echo JHtml::_('string.truncate', $item->title, $params->get('readmore_limit')); ?>
											<?php endif; ?>
										</a>
									</p>
								<?php endif; ?>
							</div>

							<?php 

							;?>
							<div class="bottom-info <?php if (!empty($item->rating_count)) echo 'has-jrate'?>">
								<!-- Show voting form -->
								<?php if (!empty($item->rating_count)) :?>
							  	<div class="rating-detail-wrap">
					          <?php
					            $scoreNum = '0';
					            if($item->rating > 0) {
					              $scoreNum = $item->rating;
					            };

					            $rateCount = '0';
					            if($item->rating_count > 0) {
					              $rateCount = $item->rating_count;
					            };

					            $scoreRate = 'far fa-star';
					            if($scoreNum > 0) {
					              $scoreRate = 'fas fa-star-half-alt';
					            };

					            if($scoreNum == 5) {
					              $scoreRate = 'fas fa-star';
					            };
					          ?> 
					            <div class="rating">
					              <span class="<?php echo $scoreRate; ?>"></span>
					              <span class="label"><?php echo $scoreNum ;?></span>
					            </div>
					            <div class="number-rating"><?php echo $rateCount.' '.Jtext::_('TPL_REVIEWS'); ?></div>
					        </div>
								  <!-- End showing -->
								<?php endif ;?>

								<?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
       					 <?php echo $item->event->afterDisplayContent; ?>

				        <?php if(!empty($customs['price-level'])): ?>
				          <div class="price-level">
				            <?php echo implode(",", $customs['price-level']) ;?>
				          </div>
				        <?php endif ;?> 
				      </div>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if($modShow && $modCat) :?>
		<div class="category-action text-center">
			<a class="btn btn-primary" href="<?php  echo JRoute::_("index.php?Itemid={$modMenu}"); ?>" title="<?php echo $modCat ;?>">
					<?php echo $modCat ;?>
			</a>
		</div>
	<?php endif ;?>
</div>
