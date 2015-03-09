<?php

require_once('src/rendering/filters/WiseChatLinksFilter.php');

class WiseChatLinksFilterTest extends PHPUnit_Framework_TestCase
{
	
	/**
	 * @dataProvider data
	 */
    public function testPositive($input, $output) {
		$this->assertEquals($output, WiseChatLinksFilter::filter($input));
    }
    
    public function data() {
		return array(
			array("test1 wp.pl test2", "test1 <a href='http://wp.pl' target='_blank' rel='nofollow'>wp.pl</a> test2"),
			array("test1 wp.pl?oo=p test2", "test1 <a href='http://wp.pl?oo=p' target='_blank' rel='nofollow'>wp.pl?oo=p</a> test2"),
			array("test1 wp.pl?oo=p%27uop test2", "test1 <a href='http://wp.pl?oo=p%27uop' target='_blank' rel='nofollow'>wp.pl?oo=p&#039;uop</a> test2"),
			array("test1 wp.pl?oo=p%3Cuop test2", "test1 <a href='http://wp.pl?oo=p%3Cuop' target='_blank' rel='nofollow'>wp.pl?oo=p&lt;uop</a> test2"),
			array("test1 http://wp.pl test2", "test1 <a href='http://wp.pl' target='_blank' rel='nofollow'>http://wp.pl</a> test2"),
			array("test1 https://wp.pl/oo/ddd/r.html test2", "test1 <a href='https://wp.pl/oo/ddd/r.html' target='_blank' rel='nofollow'>https://wp.pl/oo/ddd/r.html</a> test2"),
			array("test1 https://wp.pl/oo/ddd/r.html?oo=ww test2", "test1 <a href='https://wp.pl/oo/ddd/r.html?oo=ww' target='_blank' rel='nofollow'>https://wp.pl/oo/ddd/r.html?oo=ww</a> test2"),
			array("test1 http://wp.pl?oop=sss&eee=qqq+333 test2", "test1 <a href='http://wp.pl?oop=sss&eee=qqq+333' target='_blank' rel='nofollow'>http://wp.pl?oop=sss&amp;eee=qqq 333</a> test2"),
			array("test1 http://wp.pl?oop=sss&eee=q&lt;qq+333 test2", "test1 <a href='http://wp.pl?oop=sss&eee=q&lt;qq+333' target='_blank' rel='nofollow'>http://wp.pl?oop=sss&amp;eee=q&lt;qq 333</a> test2"),
			array("test1 wp.pl onet.pl test2", "test1 <a href='http://wp.pl' target='_blank' rel='nofollow'>wp.pl</a> <a href='http://onet.pl' target='_blank' rel='nofollow'>onet.pl</a> test2"),
		);
    }
}