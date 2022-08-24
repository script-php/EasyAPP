<?php

class ControllerTemplate extends Controller {

	function __construct($registry) {
		$this->registry = $registry;
	}
	

	function base($data='') {

		// $this->load->model('home');
		$data['base_template_logo'] = 'logo.png';
		$data['base_template_base_url'] = $this->config->base_url;
		$data['base_template_url'] = $this->config->url;

		// $all_categories = $this->model_home->categories();
		$all_categories = [];
		$categories = [];
		foreach($all_categories as $cat) {
			if($cat['parent'] == 0) {
				$categories[$cat['id']] = $cat;
				$categories[$cat['id']]['subcategories'] = [];
			}
			else {
				$categories[$cat['parent']]['subcategories'][] = $cat;
			}
		}

		// sort
		$keys = array_column($categories, 'subcategories');
		array_multisort($keys, SORT_ASC, $categories);

		$data['categories'] = $categories;

        $data['base_template_logged'] = true;
        $data['base_template_logo'] = 'http://localhost/magazin/admin/view/image/logo.png';
        $data['base_template_avatar'] = 'http://localhost/magazin/image/cache/profile-45x45.png';
        $data['base_template_firstname'] = 'firstname';
        $data['base_template_lastname'] = 'lastname';
        $data['base_template_username'] = 'username';

        $data['base_template_logout_url'] = '#';
        $data['base_template_text_logout'] = 'Logout';
        $data['base_template_profile_url'] = '#';
        $data['base_template_text_profile'] = 'Your profile';
        $data['base_template_text_navigation'] = 'Navigation';
        $data['base_template_title'] = 'title';

		return $this->load->view('base.html', $data);
	}
	

}
