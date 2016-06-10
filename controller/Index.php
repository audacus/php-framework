<?php

namespace controller;

/* 
 * Index controller
 */
class Index extends AbstractController {
	protected $noView = true;
		
	public function afterGet() {		
        \Helper::redirect("search");
	}
}
