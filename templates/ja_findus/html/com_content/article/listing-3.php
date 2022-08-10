<?php
/**
T4 Overide
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
//use Joomla\Component\Content\Administrator\Extension\ContentComponent;
use T4\Helper\J3J4;
if(!class_exists('ContentHelperRoute')){
	if(version_compare(JVERSION, '4', 'ge')){
		abstract class ContentHelperRoute extends \Joomla\Component\content\Site\Helper\RouteHelper{};
	}else{
		JLoader::register('ContentHelperRoute', $com_path . '/helpers/route.php');
	}
}

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');
require_once(__DIR__.'/../../../helper.php');

// Create shortcuts to some parameters.
$params  = $this->item->params;
$images  = json_decode($this->item->images);
$urls    = json_decode($this->item->urls);
$canEdit = $params->get('access-edit');
$user    = Factory::getUser();
$info    = $params->get('info_block_position', 0);
$og_iamge = $images->image_intro ?: $images->image_fulltext;
// Check if associations are implemented. If they are, define the parameter.
$assocParam = (Associations::isEnabled() && $params->get('show_associations'));

if ($params->get('show_intro')) {
	$separator = md5(time());
	$this->item->text = $this->item->introtext . $separator . $this->item->fulltext;
	$offset = $this->state->get('list.offset');
	$app = JFactory::getApplication();
	$app->triggerEvent('onContentPrepare', array ('com_content.article', &$this->item, &$this->item->params, $offset));
	$app->triggerEvent('onContentAfterTitle', array ('com_content.article', &$this->item, &$this->item->params, $offset));
	$app->triggerEvent('onContentBeforeDisplay', array ('com_content.article', &$this->item, &$this->item->params, $offset));
	$app->triggerEvent('onContentAfterDisplay', array ('com_content.article', &$this->item, &$this->item->params, $offset));
	$explode = explode($separator, $this->item->text);
	$this->item->introtext = array_shift($explode);
	$this->item->text = implode('', $explode);
}

// Get Extrafields
$customs 			= JATemplateHelper::getCustomFields($this->item->id, 'article');
$extrafields = new JRegistry($this->item->attribs);
$ltLocation = empty($customs['latitude-longitude']) ? '' : explode(",", implode(",", $customs['latitude-longitude'])); ;
$ltGallery = $extrafields->get('listing-gallery');
$ltAddress = empty($customs['address']) ? '' : implode(",", $customs['address']);
$businessTime = empty($customs['business-time']) ? '' : explode("-", implode(", ", $customs['business-time']));
$ltOpen = empty($businessTime) ? '' : trim($businessTime[0]);
$ltClose = empty($businessTime) ? '' : trim($businessTime[1]);
$ltStatus = JATemplateHelper::OpenClosedTime($ltOpen, $ltClose);
$ltPhone = $extrafields->get('phone');
$ltMail = $extrafields->get('mail');
$ltWebsite = $extrafields->get('website');
$ltContact = $extrafields->get('link-contact');

$ltBusOffline = $extrafields->get('offline');
$ltBusHours = $extrafields->get('business-hours');
$ltClosedOnPH = $extrafields->get('public-holiday');
$ltSocial = $extrafields->get('listing-social');
$ltMenudesc = $extrafields->get('menu-desc');
$ltMenu = $extrafields->get('listing-menu');

$ltFaqTitle = $extrafields->get('faq-title');
$ltFaqContent = $extrafields->get('listing-faq');

$app    = JFactory::getApplication();
$path   = JURI::base(true).'/templates/'.$app->getTemplate().'/';

// Rating
if (isset($this->item->rating_sum) && $this->item->rating_count > 0) {
  $this->item->rating = round($this->item->rating_sum / $this->item->rating_count, 1);
  $this->item->rating_percentage = $this->item->rating_sum / $this->item->rating_count * 20;
} else {
  if (!isset($this->item->rating)) $this->item->rating = 0;
  if (!isset($this->item->rating_count)) $this->item->rating_count = 0;
  $this->item->rating_percentage = $this->item->rating * 20;
}
$uri = JUri::getInstance();

?>
<div class="view-listing-detail style-3 item-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="https://schema.org/Article">
	<meta itemprop="inLanguage" content="<?php echo ($this->item->language === '*') ? Factory::getApplication()->get('language') : $this->item->language; ?>">

	<?php if(!empty($ltGallery)) :?>
		<!-- GALLERY -->
		<div class="listing-gallery view-slide">
			<div class="owl-carousel">
				<?php $i=0; foreach ($ltGallery as $key=>$value) : $i++; ?>
					<div class="item">
						<div class="image-item">
							<a href="<?php echo $value->listing_img; ?>"  class="html5lightbox" data-group="gallery" data-thumbnail="<?php echo JURI::base(true).'/'.$value->listing_img; ?>">
								<img src="<?php echo $value->listing_img; ?>" alt="<?php echo ($value->listing_alt) ? $value->listing_alt : ' '; ?>" />
								<span class="expand">
									<i class="fas fa-expand-alt"></i>
								</span>
							</a>
						</div>
					</div>
				<?php endforeach ;?>
			</div>
		</div>
		<!-- // GALLERY -->
		<?php endif ;?>

	<div class="container">
		<div class="listing-info-detail-wrap">
			<div class="row">
				<div class="col-lg-4 order-lg-2 mb-3">
					<div class="listing-sidebar">
						<?php if (!empty($ltLocation)): ?>
							<div class="listing-map">
	            	{jamap locations='{"location":{"0":"<?php echo $this->item->title; ?>"},"latitude":{"0":"<?php echo $ltLocation[0]; ?>"},"longitude":{"0":"<?php echo $ltLocation[1]; ?>"},"info":{"0":"<?php echo $this->item->title; ?>"},"icon":{"0":""}}' zoom='15' map_width='700' map_height='500'}{/jamap}
	            </div>
						<?php endif; ?>

                                                <?php
                                                        $busStatus = empty($ltBusOffline) || $ltBusOffline == 0;
                                                        if ($busStatus):
                                                                $busState = 'pointer-events:auto;';
                                                                $busOffText = ' ';
                                                        else:
                                                                $busState = 'pointer-events:none; opacity: 0.7; background: #ccc; cursor: not-allowed';
                                                                $busOffText = Text::_('TPL_CLOSED_DOWN');
                                                        endif;
                                                ?>
                                                <div style="<?php echo $busState;?>">
						<div class="contact-detail">
							<h5><?php echo Text::_('TPL_BUSINESS_INFO') ;?></h5>
                                                        <h4><?php echo $busOffText; ?></h4>

							<ul>

                                                       <?php if($ltBusHours && busStatus):
                                                                        $d = new DateTime();
                                                                        $day = $d->format('l');
                                                                        $daykey = strtolower($d->format('l'));
                                                                        $hourskey = strtolower(substr($day, 0, 3)).'_hours';
                                                                        $hours = $d->format('H:i');
                                                                        $today = $ltBusHours->{$daykey}.": ";
                                                                        $today_hours = $ltBusHours->{$hourskey};
                                                                        $bh = explode("-", $today_hours);
                                                                        $hstatus = JATemplateHelper::isOpen($bh[0], $bh[1], $ltClosedOnPH);
                                                                ?>
<!--                                                                <span class="stt-time stt-<?php echo strtolower(preg_replace('/\s*/', '', $hstatus)) ;?>"><?php echo $hstatus ;?></span>-->
								<span class="stt-time stt-<?php echo strtolower(substr($hstatus,0,strpos(str_pad($hstatus, 7, " ")," "))) ;?>"><?php echo $hstatus ;?></span>
                                                                <li>
                                                                        <span class="label"><?php echo Text::_('TPL_BUSINESS_HOURS');?>:</span>
                                                                                <span class="content">
                                                                                        <span class="label"><?php echo $ltBusHours->monday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->mon_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->tuesday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->tue_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->wednesday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->wed_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->thursday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->thu_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->friday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->fri_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->saturday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->sat_hours;?></span><br/>
                                                                                        <span class="label"><?php echo $ltBusHours->sunday;?> </span>
                                                                                        <span class="value"><?php echo $ltBusHours->sun_hours;?></span>
                                                                                </span>
                                                                </li>
                                                         <?php endif ;?>

 
								<?php if($ltAddress) :?>
									<li>
										<span class="label"><?php echo Text::_('TPL_LISTING_LOCATION');?>:</span>
										<span class="content"><?php echo $ltAddress ;?></span>
									</li>
								<?php endif ;?>
<!--
								<?php if($ltOpen || $ltClose) :?>
									<li>
										<span class="label"><?php echo Text::_('TPL_HOURS');?>:</span>
										<span class="content">
											<?php echo $ltOpen ;?> - <?php echo $ltClose ;?>
											<span class="stt-time stt-<?php echo strtolower(preg_replace('/\s*/', '', $ltStatus)) ;?>"><?php echo $ltStatus ;?></span>
										</span>
									</li>
								<?php endif ;?>
-->
								<?php if($ltPhone) :?>
									<li>
										<span class="label"><?php echo Text::_('TPL_PHONE');?>:</span>
										<span class="content"><a href="tel:"><?php echo $ltPhone ;?></a></span>
									</li>
								<?php endif ;?>

								<?php if($ltMail) :?>
									<li>
										<span class="label"><?php echo Text::_('TPL_EMAIL');?>:</span>
										<span class="content"><a href="mailto:<?php echo $ltMail ;?>"><?php echo $ltMail ;?></a></span>
									</li>
								<?php endif ;?>

								<?php if($ltWebsite) :?>
									<li>
										<span class="label"><?php echo Text::_('TPL_WEBSITE');?>:</span>
										<span class="content"><a href="<?php echo $ltWebsite ;?>"><?php echo preg_replace( "#^[^:/.]*[:/]+#i", "", $ltWebsite) ;?></a></span>
									</li>
								<?php endif ;?>

								<?php if($ltSocial) :?>
									<li class="info-social">
										<span class="label"><?php echo Text::_('TPL_SOCIAL');?>:</span>
										<span class="content">
											<?php $i=0; foreach ($ltSocial as $key=>$value) : $i++; ?>
												<a href="<?php echo $value->social_link; ?>" title="" class="<?php echo $value->social_font; ?>-link social-link">
													<i class="fa fa-<?php echo $value->social_font; ?>"></i>
												</a>
												
											<?php endforeach ;?>
										</span>
									</li>
								<?php endif ;?>
							</ul>
						</div>

						<?php if($ltPhone) :?>
						<div class="contact-actions">
							<div class="row">
								<?php if($ltPhone) :?>
									<div class="col-12 col-sm-6 mb-2 mb-sm-0">
										<a class="btn btn-primary d-block" href="tel:<?php echo $ltPhone ;?>" title="<?php echo $ltPhone ;?>">
											<?php echo Text::_('TPL_CALL_US') ;?>
										</a>
									</div>
								<?php endif ;?>

								<?php if($ltContact) :?>
									<div class="col-12 col-sm-6">
										<a class="btn btn-default d-block" href="tel:<?php echo $ltContact ;?>" title="<?php echo Text::_('TPL_SEND_MESSAGE') ;?>">
											<?php echo Text::_('TPL_SEND_MESSAGE') ;?>
										</a>
									</div>
								<?php endif ;?>
							</div>
						</div>
						<?php endif ;?>
					</div>
					</div>
				</div>

				<div class="col-lg-8 order-lg-1">
					<?php echo JHtml::_('content.prepare', '{loadposition breadcrumbs}'); ?>

					<?php if ($this->params->get('show_page_heading')) : ?>
					<div class="page-header">
						<h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>
					</div>
					<?php endif; ?>

					<?php if (!empty($this->item->pagination) && $this->item->pagination && !$this->item->paginationposition && $this->item->paginationrelative) {
						echo $this->item->pagination;
					}	?>

					<?php // Todo Not that elegant would be nice to group the params ?>
					<?php $useDefList = ($params->get('show_modify_date') || $params->get('show_publish_date') || $params->get('show_create_date')
					|| $params->get('show_hits') || $params->get('show_category') || $params->get('show_parent_category') || $params->get('show_author') || $assocParam); ?>

					<?php if (!$useDefList && $this->print) : ?>
						<div id="pop-print" class="btn hidden-print clearfix">
							<?php echo HTMLHelper::_('icon.print_screen', $this->item, $params); ?>
						</div>
					<?php endif; ?>

					<div class="short-info">
						<div class="row">
							<div class="col-12 col-xl-12">
								<?php if ($params->get('show_title') || $params->get('show_author')) : ?>
									<div class="page-header">
										<?php if ($params->get('show_title')) : ?>
											<h2 itemprop="headline">
												<?php echo $this->escape($this->item->title); ?>
											</h2>
										<?php endif; ?>

										<?php // Content is generated by content plugin event "onContentAfterTitle" ?>
										<?php echo $this->item->event->afterDisplayTitle; ?>
									
										<?php if (J3J4::checkUnpublishedContent($this->item)) : ?>
											<span class="label label-warning"><?php echo JText::_('JUNPUBLISHED'); ?></span>
										<?php endif; ?>

										<?php if (strtotime($this->item->publish_up) > strtotime(Factory::getDate())) : ?>
											<span class="badge badge-warning"><?php echo Text::_('JNOTPUBLISHEDYET'); ?></span>
										<?php endif; ?>

										<?php if ($this->item->publish_down && (strtotime($this->item->publish_down) < strtotime(Factory::getDate())) && $this->item->publish_down != Factory::getDbo()->getNullDate()) : ?>
											<span class="badge badge-warning"><?php echo Text::_('JEXPIRED'); ?></span>
										<?php endif; ?>

									</div>
								<?php endif; ?>

								<div class="article-aside">
								<?php if ($useDefList && ($info == 0 || $info == 2)) : ?>
										<?php echo LayoutHelper::render('joomla.content.info_block', array('item' => $this->item, 'params' => $params, 'position' => 'above')); ?>
									<?php endif; ?>

								<?php if (!$this->print) : ?>
										<?php if ($canEdit || $params->get('show_print_icon') || $params->get('show_email_icon')) : ?>
											<?php echo LayoutHelper::render('joomla.content.icons', array('params' => $params, 'item' => $this->item, 'print' => false)); ?>
										<?php endif; ?>
									<?php else : ?>
										<?php if ($useDefList) : ?>
											<div id="pop-print" class="btn hidden-print">
												<?php echo HTMLHelper::_('icon.print_screen', $this->item, $params); ?>
											</div>
										<?php endif; ?>
									<?php endif; ?>
								</div>

								<?php if ($params->get('show_intro')) :?>
									<div class="intro-text lead">
										<?php echo $this->item->introtext ;?>
									</div>
								<?php endif ;?>
							</div>
						</div>

						<div class="row">
							<div class="col-12 col-md-6 d-flex align-items-center">
								<!-- Show voting form -->
							  <?php	if ($params->get('show_vote')): ?>
							  	<div class="review-item rating">
								    <div class="rating-info pd-rating-info">
								    	<div class="rating-form-wrap">
									      <span><?php echo JText::_('TPL_VOTE_FOR_US'); ?>: </span>
									      <form class="rating-form action-vote" method="POST" action="<?php echo htmlspecialchars($uri->toString()) ?>">
									          <ul class="rating-list">
									              <li class="rating-current" style="width:<?php echo $this->item->rating_percentage; ?>%;"></li>
									              <li><a href="javascript:void(0)" title="<?php echo JText::_('JA_1_STAR_OUT_OF_5'); ?>" class="one-star">1</a></li>
									              <li><a href="javascript:void(0)" title="<?php echo JText::_('JA_2_STARS_OUT_OF_5'); ?>" class="two-stars">2</a></li>
									              <li><a href="javascript:void(0)" title="<?php echo JText::_('JA_3_STARS_OUT_OF_5'); ?>" class="three-stars">3</a></li>
									              <li><a href="javascript:void(0)" title="<?php echo JText::_('JA_4_STARS_OUT_OF_5'); ?>" class="four-stars">4</a></li>
									              <li><a href="javascript:void(0)" title="<?php echo JText::_('JA_5_STARS_OUT_OF_5'); ?>" class="five-stars">5</a></li>
									          </ul>
									          <input type="hidden" name="task" value="article.vote" />
									          <input type="hidden" name="hitcount" value="0" />
									          <input type="hidden" name="user_rating" value="5" />
									          <input type="hidden" name="url" value="<?php echo htmlspecialchars($uri->toString()) ?>" />
									          <?php echo JHtml::_('form.token') ?>
									      </form>
								     	</div>

									    <div class="rating-statics">
								      	<div class="rating-log"><span><?php echo $this->item->rating_count.' '.Jtext::_('TPL_VOTES'); ?></span></div>
								      </div>
								    </div>
								    <!-- //Rating -->

								    <script type="text/javascript">
								        !function($){
								            $('.rating-form').each(function(){
								                var form = this;
								                $(this).find('.rating-list li a').click(function(event){
								                    event.preventDefault();
								                    if (form.user_rating) {
								                    	form.user_rating.value = this.innerHTML;
								                    	form.submit();
								                    }
								                });
								            });
								        }(window.jQuery);
								    </script>
								  </div>
							  <?php endif;?>
							  <!-- End showing -->

								<?php if(!empty($customs['price-level'])): ?>
									<div class="price-level">
										<?php echo implode(",", $customs['price-level']) ;?>
									</div>
								<?php endif ;?>
							</div>

							<div class="col-12 col-md-6">
								<div class="share-social">
									<div class="social-inner">
										<span class="label"><i class="fas fa-share-alt"></i><?php echo Text::_('TPL_SHARE');?></span>
										<div class="addthis_inline_share_toolbox"></div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="listing-detail">
						<h4><?php echo Text::_('TPL_OVERVIEW') ;?></h4>
						<?php // Content is generated by content plugin event "onContentBeforeDisplay" ?>
						<?php echo $this->item->event->beforeDisplayContent; ?>

						<?php if (isset($urls) && ((!empty($urls->urls_position) && ($urls->urls_position == '0')) || ($params->get('urls_position') == '0' && empty($urls->urls_position)))
							|| (empty($urls->urls_position) && (!$params->get('urls_position')))) : ?>
							<?php echo $this->loadTemplate('links'); ?>
						<?php endif; ?>

						<?php if ($params->get('access-view')) : ?>
							<?php echo LayoutHelper::render('joomla.content.full_image', $this->item); ?>

							<?php if (!empty($this->item->pagination) && $this->item->pagination && !$this->item->paginationposition && !$this->item->paginationrelative) :
								echo $this->item->pagination;
							endif; ?>

						<?php if (isset ($this->item->toc)) : echo $this->item->toc; endif; ?>

						<div itemprop="articleBody" class="article-body">
							<?php echo $this->item->text; ?>
						</div>

						<?php if ($info == 1 || $info == 2) : ?>
							<?php if ($useDefList) : ?>
								<?php echo LayoutHelper::render('joomla.content.info_block', array('item' => $this->item, 'params' => $params, 'position' => 'below')); ?>
							<?php endif; ?>

							<?php if ($params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
								<?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>
								<?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
							<?php endif; ?>
						<?php endif; ?>


						<?php if (isset($urls) && ((!empty($urls->urls_position) && ($urls->urls_position == '1')) || ($params->get('urls_position') == '1'))) : ?>
							<?php echo $this->loadTemplate('links'); ?>
						<?php endif; ?>

						<?php // Optional teaser intro text for guests ?>
						<?php elseif ($params->get('show_noauth') == true && $user->get('guest')) : ?>
							<?php echo LayoutHelper::render('joomla.content.intro_image', $this->item); ?>
							<?php echo HTMLHelper::_('content.prepare', $this->item->introtext); ?>
							<?php // Optional link to let them register to see the whole article. ?>
						
							<?php if ($params->get('show_readmore') && $this->item->fulltext != null) : ?>
							<?php $menu = Factory::getApplication()->getMenu(); ?>
							<?php $active = $menu->getActive(); ?>
							<?php $itemId = $active->id; ?>
							<?php $link = new Uri(Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId, false)); ?>
							<?php $link->setVar('return', base64_encode(ContentHelperRoute::getArticleRoute($this->item->slug, $this->item->catid, $this->item->language))); ?>

							<p class="com-content-article__readmore readmore">
								<a href="<?php echo $link; ?>" class="register">
								<?php $attribs = json_decode($this->item->attribs); ?>
								<?php
								if ($attribs->alternative_readmore == null) :
									echo Text::_('COM_CONTENT_REGISTER_TO_READ_MORE');
								elseif ($readmore = $attribs->alternative_readmore) :
									echo $readmore;
									if ($params->get('show_readmore_title', 0) != 0) :
										echo HTMLHelper::_('string.truncate', $this->item->title, $params->get('readmore_limit'));
									endif;
								elseif ($params->get('show_readmore_title', 0) == 0) :
									echo Text::sprintf('COM_CONTENT_READ_MORE_TITLE');
								else :
									echo Text::_('COM_CONTENT_READ_MORE');
									echo HTMLHelper::_('string.truncate', $this->item->title, $params->get('readmore_limit'));
								endif; ?>
								</a>
							</p>
							<?php endif; ?>
						<?php endif; ?>
					</div>

					<?php if(!empty($customs['amenities'])): ?>
						<div class="section-listing-info amenities">
							<?php echo implode($customs['amenities-info']) ;?>
							<ul class="row row-cols-1 row-cols-md-2 row-cols-xl-3">
								<?php echo "<li>". implode("</li><li>",$customs['amenities'])."</li>" ;?>
							</ul>
						</div>
					<?php endif ;?>

					<?php if(!empty($ltGallery)) :?>
					<!-- Menu -->
					<div class="section-listing-info listing-menu">
						<?php if($ltMenudesc) :?>
							<?php echo $ltMenudesc ;?>
						<?php endif ;?>

						<div class="row row-cols-1 row-cols-md-2 v-gutters">
							<?php $i=0; foreach ($ltMenu as $key=>$value) : $i++; ?>
								<div class="item">
									<div class="menu-image">
										<img src="<?php echo $value->menu_image; ?>" alt="<?php echo ($value->menu_title) ? $value->menu_title : ' '; ?>" />
									</div>

									<div class="menu-content">
										<h6><?php echo $value->menu_title; echo ($value->menu_price) ? " (".$value->menu_price.")" : ''; ?></h6>
										<span><?php echo $value->menu_desc; ?></span>
										<span>
        						                                <?php	foreach ($value->menu_type as $food) {                            // cycle thru food types
                                        							    $icon = JATemplateHelper::getFoodIcon($food, false);            // get icon for food type
					                                		            $file = "/images/icons/".$icon;                                 // location of icons
                        							                    ($icon) ? $ic = "<img src='".$file."'/>" : $ic = "";            // is there an icon or not?
					                		                            echo $ic." ";                                                   // display icon or nothing
/**     		                					                    <i class="fas <?php echo JATemplateHelper::getFoodIcon($food, true);?>"></i> **/
					   		                                        endofeach; }
                                        						?>
						                                </span>
									</div>
								</div>
							<?php endforeach ;?>
						</div>
					</div>
					<!-- // menu -->
					<?php endif ;?>

					<?php if(!empty($ltFaqContent)) :?>
					<!-- FAQ -->
					<div class="section-listing-info listing-faq">
						<?php if($ltFaqTitle) :?>
							<?php echo $ltFaqTitle ;?>
						<?php endif ;?>

						<div class="accordion" id="accordionFAQ">
							<?php $i=0; foreach ($ltFaqContent as $key=>$value) : $i++; ?>
						  <div class="accordion-item">
						    <h2 class="accordion-header" id="heading-<?php echo $i ;?>">
						      <button class="accordion-button <?php if($i != 1) echo 'collapsed';?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $i ;?>" aria-expanded="true" aria-controls="collapse-<?php echo $i ;?>">
						        <?php echo $value->faq_title; ?>
						      </button>
						    </h2>
						    <div id="collapse-<?php echo $i ;?>" class="accordion-collapse collapse <?php if($i == 1) echo 'show';?>" aria-labelledby="heading-<?php echo $i ;?>" data-bs-parent="#accordionFAQ">
						      <div class="accordion-body">
						       <?php echo $value->faq_content; ?>
						      </div>
						    </div>
						  </div>
						  <?php endforeach ;?>
						</div>
					</div>
					<!-- // FAQ -->
					<?php endif ;?>

					<?php if ($info == 0 && $params->get('show_tags', 1) && !empty($this->item->tags->itemTags)) : ?>
						<div class="section-listing-info listing-tags">
							<h3><?php echo Text::_('TPL_TAGS') ;?></h3>
							<?php $this->item->tagLayout = new FileLayout('joomla.content.tags'); ?>
							<?php echo $this->item->tagLayout->render($this->item->tags->itemTags); ?>
						</div>
					<?php endif; ?>

					<?php // Content is generated by content plugin event "onContentAfterDisplay" ?>
						<?php echo $this->item->event->afterDisplayContent; ?>

					<?php
						if (!empty($this->item->pagination) && $this->item->pagination && $this->item->paginationposition && $this->item->paginationrelative) :
							echo $this->item->pagination;
						endif;
					?>

					<?php
							if (!empty($this->item->pagination) && $this->item->pagination && $this->item->paginationposition && !$this->item->paginationrelative) :
								echo $this->item->pagination;
							endif;
						?>
				</div>
			</div>

			
		</div>
	</div>
</div>

<script>
	(function($){
		$(document).ready(function(){
			// Remove container
	  	$('.t4-wrapper').addClass('view-listing-detail-3');
	  	$('#t4-main-body > .t4-section-inner').removeClass('container');

			<?php if(!empty($ltGallery)) :?>
				$(".listing-gallery .owl-carousel").owlCarousel({
		      addClassActive: true,
		      items: 1,
		      singleItem : true,
		      itemsScaleUp : true,
		      nav : true,
		      navText : ["<i class='fas fa-arrow-left'></i>", "<i class='fas fa-arrow-right'></i>"],
		      dots: false,
		      merge: false,
		      mergeFit: true,
		      margin: 24,
		      dotsEach: true,
		      animateOut: 'fadeOut',
		      autoplaySpeed: 1200,
		      smartSpeed: 1400,
		      loop: true,
		      autoplay: false,
		      responsive : {
						0 : {
					    items: 1
						},
						767 : {
					    items: 1
						},
						1200 : {
					    items: 1
						}
					}
		    });
					
				$(".listing-gallery .html5lightbox").html5lightbox({
					autoslide: true,
					showplaybutton: false,
					jsfolder: "<?php echo $path . 'js/html5lightbox/' ?>"
				});
			<?php endif ;?>
		});
	})(jQuery);
</script>
