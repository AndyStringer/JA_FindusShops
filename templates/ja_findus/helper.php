<?php
/**
 * ------------------------------------------------------------------------
 * ja_findus_tpl
 * ------------------------------------------------------------------------
 * Copyright (C) 2004-2018 J.O.O.M Solutions Co., Ltd. All Rights Reserved.
 * @license - Copyrighted Commercial Software
 * Author: J.O.O.M Solutions Co., Ltd
 * Websites:  http://www.joomlart.com -  http://www.joomlancers.com
 * This file may not be redistributed in whole or significant part.
 * ------------------------------------------------------------------------
 */


class JATemplateHelper
{
	public static function getCustomFields($id, $context) {
		if ($context == 'article')
			$context = 'com_content.article';
		else if ($context == 'contact')
			$context = 'com_contact.contact';
		else if ($context == 'user')
			$context = 'com_users.user';
		$currentLanguage = JFactory::getLanguage();
		$currentTag = $currentLanguage->getTag();

		$sql = 'SELECT fv.value, fg.title AS gtitle, f.title AS ftitle, f.name
				FROM #__fields_values fv
				LEFT JOIN #__fields f ON fv.field_id = f.id
				LEFT JOIN #__fields_groups fg ON fg.id = f.group_id
				WHERE fv.item_id = '.$id.'
				AND f.context = "'.$context.'"
				AND f.language IN ("*", "'.$currentTag.'")
				AND f.access = 1
				';
			// echo $sql;
		$db = JFactory::getDbo();
		$db->setQuery($sql);
		$result = $db->loadObjectList();
		$arr = array();
		foreach ($result AS $r) {
			if(self::isJson($r->value) && version_compare(JVERSION, '4', 'ge')){
				$imgArr = json_decode($r->value,true);
				$img = $imgArr['imagefile'];
			}else{
				$img = $r->value;
			}
			$arr[$r->name][] = $img;
		}

		return $arr;
	}

	public static function isJson($string) {
		return ((is_string($string) &&
			(is_object(json_decode($string)) ||
			is_array(json_decode($string))))) ? true : false;
	}

	public static function OpenClosedTime($startTime, $endTime){
		$config = JFactory::getConfig();
		date_default_timezone_set($config->get('offset', 'UTC'));
		if(empty($startTime) && empty($endTime)) return Jtext::_('TPL_CLOSED');
		$current_time = time();
		// default status
		$status = Jtext::_('TPL_CLOSED');
		// get current time object
		$currentTime = (new DateTime())->setTimestamp($current_time);

		// create time objects from start/end times
		$startTime = DateTime::createFromFormat('h:i A', $startTime);
		$endTime   = DateTime::createFromFormat('h:i A', $endTime);
		// check if current time is within a range
		if (($startTime < $currentTime) && ($currentTime < $endTime)) {
			$status = Jtext::_('TPL_OPEN_NOW');
		}elseif(($startTime > $endTime) && (($startTime < $currentTime) || ($currentTime < $endTime))){
			$status = Jtext::_('TPL_OPEN_NOW');
		}
		return $status;
	}

	public static function isOpen($startTime, $endTime, $openOnPH){
		// Determine if the store is open on the current day
		// It may be "closed until further notice" or "open 24 hours"
		// Also check if the store is open on public holidays
		// and notify 30 minutes before closing time or before opening time

//		if (empty($startTime)) return "";
//		JATemplateHelper::getPublicHolidays(14, 28, 0);
//		if ($startTime == "Closed") return "Closed";

		$d = new DateTime();
		$today = $d->format('Y-m-d');
		$status = '';
                if (!$openOnPH) {
                        foreach (JATemplateHelper::getPublicHolidays() as $date) {         // Check public holiday closures
                                if ($date == $today) {
                                        $status = Jtext::_('TPL_CLOSED_PUBLIC_HOLIDAYS');
                                }
                        }
			if ($status) return $status;
                }

		$margin = "-30 minutes";                                                         // opening/closed soon margin in seconds (1880 = 30mins)
		$day = $d->format('d');
		$hours = $d->format('H:i');
		$startTime = trim($startTime);
		$endTime = trim($endTime);
		$hstart = DateTime::createFromFormat('!H:i', $startTime);
		if (empty($hstart)) return $startTime;						// Not a valid time in the start time slot, so return the text

		// Into the next day time checking from https://stackoverflow.com/questions/27131527/php-check-if-time-is-between-two-times-regardless-of-date
		$hstop = DateTime::createFromFormat('!H:i', $endTime);
		$htime = DateTime::createFromFormat('!H:i', $hours);
		$nstart = DateTime::createFromFormat('!H:i', $startTime);
		$nstop = DateTime::createFromFormat('!H:i', $endTime);

                $nstart = $nstart->modify($margin);
                $hstatus = ($htime >= $nstart && $htime <= $hstart) || ($htime >= $htime->modify('+1 day') && $htime <= $hstart); // open soon, in $margin minutes?
                if ($hstatus) {
                        return Jtext::_('TPL_OPENING_SOON');
                }

		$htime = DateTime::createFromFormat('!H:i', $hours);
                $nstop = $nstop->modify($margin);
                $hstatus = ($htime >= $nstop && $htime <= $hstop) || ($htime <= $htime->modify('+1 day') && $htime <= $hstop); // closing soon, in $margin minutes?
                if ($hstatus) {
                        return Jtext::_('TPL_CLOSING_SOON');
                }

		$htime = DateTime::createFromFormat('!H:i', $hours);
                if ($hstart > $hstop) $hstop->modify('+1 day');
                $hstatus = ($hstart <= $htime && $htime <= $hstop) || ($hstart <= $htime->modify('+1 day') && $htime <= $hstop); // open or closed?
                if ($hstatus) { 
                        return Jtext::_('TPL_OPEN');
                }

		return Jtext::_('TPL_CLOSED');

	}

	public static function getPublicHolidays() {
		// Uses Jevent extension to assign dates as events in Public Holiday category
		// Jevents stores categories in standrad Joomla category table, as com_jevents
		// each event is stored in jevents_vevent table with actegory and publsihed state, with pointer to detail in jevents_vevdetail table
		// the dtend seems to be the date of the event (or at least its end time - converted to date format YY-MM-DD
		// returned in array of dates
		$sql = 'SELECT 	date_format(from_unixtime(dtend), "%Y-%m-%d") AS holiday
			FROM pm068_jevents_vevdetail,
				(SELECT detail_id
				 FROM pm068_jevents_vevent,
					(SELECT id
					 FROM pm068_categories
					 WHERE title = "Public HolidayS" AND extension = "com_jevents"
					) AS category
				 WHERE catid = category.id AND state = 1
				) AS detail
			WHERE evdet_id = detail.detail_id';
		$db = JFactory::getDbo();
		$db->setQuery($sql);
		$result = $db->loadObjectList();
		$arr = array();
		$i=0;
		foreach ($result AS $r) {
			$arr[$i++] = $r->holiday;
		}
		return $arr;
	}

	public static function getFoodIcon($food, $fa = false) {
		$icon = false;
		switch ($food) {
			case "Pizza":
				($fa)? $icon = "fa-pizza-slice" : $icon = "pizza-icon.png";
				break;
			case "Pasta":
				($fa)? $icon = "fa-utensils" : $icon = "pasta-icon.png";
				break;
			case "Sandwiches":
				($fa)? $icon = "fa-hamburger" : $icon = "sandwiches-icon.png";
				break;
			case "Sushi":
				($fa)? $icon = "fa-fish" : $icon = "sushi-icon.png";
				break;
			case "Steak":
				($fa)? $icon = "fa-cutlery" : $icon = "steak-icon.png";
				break;
			case "Vegetarian":
				($fa)? $icon = "fa-leaf" : $icon = "vegetarian-icon.png";
				break;
			case "Vegan":
				($fa)? $icon = "fa-leaf" : $icon = "vegan-icon.png";
				break;
			case "Gluten Free":
				($fa)? $icon = "fa-leaf" : $icon = "gluten-free-icon.png";
				break;
			case "Halal":
				($fa)? $icon = "fa-leaf" : $icon = "halal-icon.png";
				break;
			case "Kosher":
				($fa)? $icon = "fa-leaf" : $icon = "kosher-icon.png";
				break;
			case "Organic":
				($fa)? $icon = "fa-leaf" : $icon = "organic-icon.png";
				break;
			case "Vegetarian Friendly":
				($fa)? $icon = "fa-leaf" : $icon = "vegetarian-friendly-icon.png";
		}
		return $icon;
	}

}
?>
