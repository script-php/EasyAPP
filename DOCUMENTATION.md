# Documentation EasyAPP

## Basic directory structure
The basic file structure for your project will follow the MVCL framework structure.
The structure looks like:
- controller
- model
- view
- language

Every page will require at least a single file in each of the view and controller folders. Most will require a file in each of the model and language folders. It is recommended that you use the same name as the controller for easy development, except the view file has a different suffix (.tpl, .html, ...). We will go through these files one by one.

## Controller
The first file you make will be the controller for your page.

#### Accessed via URL
The controller is the only file to be accessed by URL in EasyAPP 
The URL will look like: 
```
index.php?route=my_page
```
OR 
```
index.php?route=my_folder/my_page.
```
As a result, the controller file will have a function defined as public function index(). This is a publicly accessible 'page' that is loaded by the URL.
You can have more functions defined as public and you can access them in this way:
```
index.php?route=my_page|method
```

## View 
The second required file for your page's interface is the view file. This will be created in the app/view/ folder, and will have what suffix you want (.tpl, .html, ...).
In this file you will create the HTML template for your website output.
In the view, you will be able to access the text from the language that the controller file stored as a PHP variable. See the example below how it works.

#### Show output of you page
If you want to display a view in your page you should use the following code:
```
echo $this->load->view('my_folder/my_view.html', $data);
```
Where 'my_folder/my_view.html' is the path of your view file and $data is the array of parameters that turns into variables on view file.


## Language
The third file you will usually need to create for any page is the language file(s). You can create one file that store all texts per language for all pages OR you can create a language file for each page. All depends by how you want to structure the project. The language file will live in the app/language/ folder. It simply contains a PHP associative array called $_, which contains the internal name as the key and the translation as the value. 
See the example below how you can use it.

#### Language usage 
The controller file is the place where you can load the language files to convert text into variables to be utilized in the view file.
The controller brings the text stored in the language file, and turns them into variables that can be echoed in the view file to displayed text. This is especially useful for managing translations of your project.
Instead of modifying your view file every time you have a new translation to change each piece of text inside, you just need to modify the text in your language file, and the variables will remain the same in the controller and the view.

The piece of code below will load the language file inside in your page controller. Inside the parentheses you will need to include the path to the language file from inside the language folder.
```
$this->load->language('my_folder_language/my_file_language');
```
Once the language file is loaded into the controller, you can store its text into a php variable with the use of the $data array. Example:
```
$this->data['text'] = $this->language->get('text');
```
The $this->language->get('text') will grab the text from the $_['text'] variable inside of the language file we just loaded above. Every element of the data array will be converted into its own variable. The $data['text'] will become $text for the view file. The $text variable can be echoed in the view's file wherever needed:
```
<p><?php echo $text; ?></p>
```

## Model
The model is the file where you have all database queries. If one of your pages need to use a query used into other page you can just load that model page and use it. You dont have to write same query two times in your project. Just share the model between pages. See the example below how it works.

#### Loading model files
Loading model files into your controller file will allow your page to utilize database queries from other pages instead of repeating same queries for multiple pages.
The functions inside the model files interact with the project's database and to add/pull important information for your page.
Your page can load any model file using the following code:
```
$this->load->model('my_folder/my_model');
```
You will need to specify the path to the file you want to load from the model folder within the parentheses. The code above will load the settings class so we have access to the functions within the ModelMyFolderMyModel class in our model file. Use the following format in your code to call a function from a loaded model file:
```
$this->model_my_folder_my_model->myFuntion();
```
The underscores refer to the file designations for model/my_folder/my_model.php. If you have a model file included for your page your code would follow the format mentioned above, since the model file is uploaded to model folder.
```
$this->load->model('my_folder/my_model');
$this->model_my_folder_my_model->myFunction();
```
The code above will load the my_model.php stored in model/module/my_module.php.

Attention: Instead of using spaces in file names for your module, use underscores.

# Class naming
The name of controllers and models should follow this simple rules:
1. if the file of your page is "my_page.php" then the name of the class page it will be "ControllerMyPage" / "ModelMyPage"
2. if the file of your page is into a folder "my_folder/my_page.php" then the name of the class page it will be "ControllerMyFolderMyPage" / "ModelMyFolderMyPage"


# Utils

## Requests
GET:
```
$this->request->get['name'];
```
POST:
```
$this->request->post['name'];
```
## Cookie
```
$this->request->cookie['name'];
```
## Session
See sessions:
```
$this->request->session;
```
Set a new session:
```
$this->request->session['session_key'] = 'value';
```
## All requests
```
$this->request;
```
## Server
```
$this->server;
```
## Files
```
$this->files;
```
## IP
```
$this->ip;
```
## Mails
```
$this->mail->send([
    'to'            => '', // string or array with mail/s
    'from'          => '', // string
    'sender'        => '', // string
    'reply_to'      => '', // string
    'subject'       => '', // string
    'text'          => '', // string
    'html'          => '', // string
    'attachments'   => [] // array with files
]);
```