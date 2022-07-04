To create a page, you should create a controller in app/controller.
The files should be like this: 
1. my_page.php
2. my_folder/my_new_page.php
3. my_folder/my_subfolder/other_page.php

Every controller name class should have this form:

1. for "my_page.php"  the name of class will be "ControllerMyPage" 
2. for "my_folder/my_new_page.php"  the name of class will be "ControllerMyFolderMyNewPage"
3. for "my_folder/my_subfolder/other_page.php"  the name of class will be "ControllerMyFolderMySubfolderOtherPage"

The class of controller will be like this:


class ControllerMyFolderMySubfolderOtherPage extends Controller {
    public $registry;

    function __construct($registry) {
		$this->registry = $registry;
	}

    function index() {
        echo 'this is my page';
    }
}

