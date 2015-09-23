<?php 

/* mike.php - the micro drop-in site editor */
/* PHP 5.4+ */	

/*
 * Things to do
 * 0. Include Zepto JS (DONE)
 * 0. Style the file list page
 * 0. Get the list of files by AJAX
 * 0. Save a file
 * 0. Upload a file.
 */

$users = ['admin' => '5f4dcc3b5aa765d61d8327deb882cf99'];

/* output buffering - switch on to allow headers to be updated */
ob_start();

class Mike {
	public static $version = "0.1";
	public static $this_file = "mike.php";
	public static $isRouteProcessed = false;
	private static $requestUri;
        private static $contentTypes = array(
            'css' => "text/css",
            'js' => 'text/javascript',
            'text' => 'text/plain',
            'json' => 'application/json'
            
        );

	/**
	 * Routes a get request and executes the routed function.
	 *
	 * @param string $route
	 * @param function $function
	 */
	public static function get($route, $function)
	{
		// Check if the request method type
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
			static::processRoute($route, $function);
		}
	}

	/**
	 * Routes a post request and executes the routed function.
	 *
	 * @param string $route
	 * @param function $function
	 */
	public static function post($route, $function)
	{
		// Check the request method type
		if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
			static::processRoute($route, $function);
		}
	}

	/**
	 * Determines the base URI of the app.
	 *
	 * @return string
	 */
	public static function baseUri($segments = null)
	{
		return str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']) . ($segments ? trim($segments, '/') : '');
	}

	/**
	 * Porcesses the route.
	 *
	 * @access private
	 */
	private static function processRoute($route, $function)
	{
		if (static::$isRouteProcessed) {
			return;
		}

		// Check if the request is empty
		if (static::requestUri() == '') {
			static::$requestUri = '/';
		}

		// Match the route
		if (preg_match("#^{$route}$#", static::requestUri(), $matches)) {
			unset($matches[0]);
			call_user_func_array($function, $matches);
			static::$isRouteProcessed = true;
		}
	}

	/**
	 * Determines the requested URL.
	 *
	 * @return string
	 * @access private
	 */
	public static function requestUri()
	{
		// Check ff this is the first time getting the request uri
		if (static::$requestUri === null) {

			$baseMikePath = "/" . static::$this_file;

			// Check if there is a PATH_INFO variable
			// Note: some servers seem to have trouble with getenv()
			$path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
			if (trim($path, '/') != '' && $path != $baseMikePath) {
				return static::$requestUri = $path;
			}

			// Check if ORIG_PATH_INFO exists
			$path = str_replace($_SERVER['SCRIPT_NAME'], '', (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO'));
			if (trim($path, '/') != '' && $path != $baseMikePath) {
				return static::$requestUri = $path;
			}

			// Check for ?uri=x/y/z
			if (isset($_REQUEST['url'])) {
				return static::$requestUri = $_REQUEST['url'];
			}

			// Check the _GET variable
			if (is_array($_GET) && count($_GET) == 1 && trim(key($_GET), '/') != '') {
				return static::$requestUri = key($_GET);
			}

			// Check for QUERY_STRING
			$path = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
			if (trim($path, '/') != '') {
				return static::$requestUri = $path;
			}

			// Check for requestUri
			$path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
			if (trim($path, '/') != '' && $path != $baseMikePath) {
				return static::$requestUri = str_replace(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '', $path);
			}

			// I dont know what else to try, leave it...
			return static::$requestUri = '';
		}

		return static::$requestUri;
	}// end of requestUri
        
        /*
         * Sets the content type 
         */
        public static function set_content_type($type) {
            header('Content-Type: ' . static::$contentTypes[strtolower($type)]);
        }
        
        /*
         * Renders a layout page with content 
         */
        public static function render($layout, $body, $options = NULL) {
		// first append the body 
		$page = $layout;

		$page = str_replace("{{BODY}}", $body, $layout);

		if ($options != NULL) {
			// check headers and apply any headers
			if (array_key_exists('headers', $options)) {
				foreach($options['headers'] as $header) {
					header($header);
				}
			} else {
				// use default header
				header("Content-Type: text/html");
			}

			// apply title
			if (array_key_exists('title', $options)) {
				$page = str_replace("{{TITLE}}", $options['title'], $page);
			} else {
				$page = str_replace("{{TITLE}}", "mike.php", $page);
			}

			// apply scripts 
			if (array_key_exists('scripts', $options)) {
				$script_tag = [];
				foreach($options['scripts'] as $script) {
					$script_tag[] = '<script type="text/javascript" src="' . $script . '"></script>';
				}
				$page = str_replace("{{SCRIPTS}}", implode($script_tag), $page);
			} else {
				$page = str_replace("{{SCRIPTS}}", "", $page);
			}

			// apply styles
			if (array_key_exists('styles', $options)) {
				$styles_tag = [];
				foreach($options['styles'] as $style) {
					$styles_tag[] = '<link rel="stylesheet" type="text/css" href="' . $style . '">';
				}
				$page = str_replace("{{STYLES}}", implode($styles_tag), $page);	
			} else {
				$page = str_replace("{{STYLES}}", "", $page);
			}

		} else {
			// remove the tags - since they are nothing to replace them with.
			$page = str_replace("{{TITLE}}", "Mike.php", $page);
			$page = str_replace("{{SCRIPTS}}", "", $page);
			$page = str_replace("{{STYLES}}", "", $page);
		}

		// send the output..
		echo $page;
        }//end of render
}// end of Mike

/* MARKUP */
static $layout = <<<'EOD'
	<!doctype html>
	<html>
		<head>
			<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
			<meta http-equiv="Pragma" content="no-cache" />
			<meta http-equiv="Expires" content="0" />
			<title>{{TITLE}}</title>
			{{STYLES}}
			{{SCRIPTS}}
		</head>
		{{BODY}}
	</html>
EOD;

static $login_page = <<<'EOD'
    <body id="mike-login">
        <header>
            <span class="product">mike.php</span>
            <span class="slogan">The micro drop-in site editor</span>
        </header>
        
        <form action="login" method="post">
        <div id="login-form">
        <h1>Login</h1>
        <fieldset>
                <legend>Enter your credentials</legend>
                <div>
                <input type="text" name="name" class="entry" placeholder="Enter username"/>
                </div>
                <div>
                <input type="password" name="password" class="entry" placeholder="Enter password"/>
                </div>
        </fieldset>
        <input type="submit" value="Login" class="btn"/>
        </div>
            </form>
    </body>
EOD;

static $file_page = <<<'EOD'
    <body id="mike-filelist">
        <header>
            <span class="product">mike.php</span>
            <span class="slogan">The micro drop-in site editor</span>
        </header>
        
        <div class="container2">
            <h1>File List</h1>
                <div class="button-bar">
                    <button class="btn" >save</button>
                </div>
                <div class="container1">
                        <div id="filelist" class="filelist">
                                {{TABLE}}
                        </div>
                        <div id="fileedit" class="fileedit">
  
                                <textarea id="filecontent"></textarea>
                        </div>
                </div>
        </div>
        {{FILEMGR_JS}}
    </body>
EOD;

/*
 * Main page CSS
 */
static $style_css = <<<'EOD'
	html, body, .container, ul { padding: 0; margin: 0}
	html, body { background-color: #2067b2; color: #fff; font-family: Helvetica, Arial, sans-serif; }
        header { background-color: #3d7bbc; height: 40px; width: 100%; font-weight: 100; padding: 10px 0 0 10px;}
        header .product { font-size: 120%; }
        header .slogan { font-size: 80%; }
        h1, h2, h3, h4 { font-weight: 100; }
	/*.container2{ clear: left; float: left; width: 100%; overflow: hidden;}
	.container1{ float: left; width: 100%; position: relative; right: 50%; }
	#filelist, #fileedit { float: left; width: 46%; position: relative; left: 52%; overflow: hidden; }
	#fileedit {left: 56%; position: relative;}*/
        #login-form { width: 300px; margin: 0 auto; }
        #login-form fieldset { border: none; margin: 0; padding: 0;}
        #login-form .entry { border: none; line-height: 2em; font-size: 120%; padding: 10px; color: #2067b2; }
        .btn { background-color: #4da7dd; padding: 10px; border: none; cursor: pointer; font-size: 100%; color: white; font-weight: 100; }
        .btn:hover { background-color: #3d7bbc; }
        /*textarea{ width: 98%; padding:1%; border:none; }*/
EOD;

/*
 * FileManager CSS
 */
static $filemgr_css = <<<'EOD'
ul#filetable {
    list-style: none;
    list-style-type: none;
    padding:0;
    line-height: 2em;
    color: #666;
}

ul#filetable li {
    cursor: pointer;
    background-color: white;
    padding-left: 6px;
}

ul#filetable li:hover {
    color: black;
}

textarea {
	font-family: Consolas, Inconsolata, monospace;
	font-size: 12pt;
}

html, body { height: 100%; }
header{ position: fixed; z-index: 1;}

.container1 { position: relative; height: calc(100% - 100px);   }
.filelist { width: 49%; float: left; height: 100%;}
.fileedit { width: 50%; float: right; }
.fileedit textarea { position: absolute; top:0; bottom:0; width: 49%; }
h1 { border-bottom: solid 1px #fff; }
.container2 { padding: 6px; height: 100%; width: 99%; position:relative; top: 40px;  }
.button-bar { width: 49%; margin-left: 50%; }
EOD;

/*
 * FileManager JS
 */
static $filemgr_js = <<<'EOD'
<script>
    var TAB_KEY_CODE = 9,
        SOFT_TAB = '    ',
        SOFT_TAB_LENGTH = SOFT_TAB.length, 
	MikeFileManager = (function() {
            function getFile(filename) {
                            $.post('/mike.php/file', {'file' : filename}, function(data) {
                                    $('#filecontent').val(data);
                            })
                    };
                    
            function getListing() {
                $.getJSON('/mike.php/filelist.json', function(data) {
                    console.log(data);
                });
            }
            
            function saveFile() {
            };

            // public API
            return {
                    getFile : getFile,
                    saveFile : saveFile,
                    getListing : getListing
            }
        })();
    $d(function() {
    
        MikeFileManager.getListing();
        
        $("li.file").on('click', function(e) {
            var filename = $(e.currentTarget).attr('data-file');
            MikeFileManager.getFile(filename);
        });


        // pressing tab should insert spaces instead of focusing another element
        $('textarea').on("keydown", function(event) {
            var value = textarea.value;
            var caret = textarea.selectionStart;

            // if tab is pressed, insert four spaces
            if (event.keyCode === TAB_KEY_CODE) {
              textarea.value = value.substring(0, caret) + SOFT_TAB +
                value.substring(caret);

              // move caret to after soft tab
              textarea.setSelectionRange(caret + SOFT_TAB_LENGTH, caret +
                SOFT_TAB_LENGTH);

              // prevent default tab action that shifts focus to the next element
              event.preventDefault();
            }
        });
    });
</script>
EOD;

/*
 *  140medley - micro jQuery replacement.
 */
static $micro_js = <<<'EOD'
var t=function(a,b){return function(c,d){return a.replace(/#{([^}]*)}/g,function(a,e){return Function("x","with(x)return "+e).call(c,d||b||{})})}},s=function(a,b){return b?{get:function(c){return a[c]&&b.parse(a[c])},set:function(c,d){a[c]=b.stringify(d)}}:{}}(this.localStorage||{},JSON),p=function(a,b,c,d){c=c||document,d=c[b="on"+b],a=c[b]=function(e){return d=d&&d(e=e||c.event),(a=a&&b(e))?b:d},c=this},m=function(a,b,c){for(b=document,c=b.createElement("p"),c.innerHTML=a,a=b.createDocumentFragment();b=c.firstChild;)a.appendChild(b);return a},$=function(a,b){return a=a.match(/^(\W)?(.*)/),(b||document)["getElement"+(a[1]?"#"==a[1]?"ById":"sByClassName":"sByTagName")](a[2])},j=function(a){for(a=0;4>a;a++)try{return a?new ActiveXObject([,"Msxml2","Msxml3","Microsoft"][a]+".XMLHTTP"):new XMLHttpRequest}catch(b){}},jx=function(a,b,c,d,e,f,g){g=j(),g.open(c,a,f),"POST"==c&&g.setRequestHeader("Content-Type","application/x-www-form-urlencoded"),g.ondocumentreadychange=function(){4===g.readyState&&(g.status>=200&&g.status<400?b&&b(g.responseText):e&&e(g.responseText))},g.send(data)};
EOD;

/*
 * 404 page
 */
static $page_404 = <<<'EOD'
   <body>
        <header>
            <span class="product">mike.php</span>
            <span class="slogan">The micro drop-in site editor</span>
        </header>
       <h1>{{CONTENT}}</h1>
   </body>
EOD;

/* MAIN ENTRY POINT */
/* 
 * Base path
 */
Mike::get('/', function() {
    session_start();
    if (isset($_SESSION['username'])) {
        // user is logged in..
        header('Location: /'. Mike::$this_file. '/list');
    } else {
        header('Location: /' . Mike::$this_file . '/login');
    }
    
});

/* Zepto library */
Mike::get('/js/zepto.js', function() {
     Mike::set_content_type('js');
     echo $GLOBALS['zepto_js'];
});

/* 140MEDLEY library - micro jQuery replacement */
Mike::get('/js/140medley.js', function() {
    Mike::set_content_type('js');
    echo $GLOBALS['micro_js'];
});

/*
 * Stylesheet for Mike
 */
Mike::get('/css/style.css', function() {
    Mike::set_content_type('css');
    echo $GLOBALS['style_css'];
});

/*
 * Stylesheet for file manager
 */
Mike::get('/css/filemgr.css', function() {
     Mike::set_content_type('css');
    echo $GLOBALS['filemgr_css'];
});

/* 
 * Login page
 */
Mike::get('/login', function() {
    Mike::render($GLOBALS['layout'], $GLOBALS['login_page'], 
            ['styles' => ['css/style.css'], 
            'title' => 'Login - Mike.php',
            'headers' => ['Cache-Control: no-cache, no-store, must-revalidate','Pragma: no-cache','Expires: 0']
		]);
});

/*
 * Logout
 */
Mike::get('/logout', function() {
    session_start();
    session_destroy();
    header('Location: ' . Mike::$this_file . '/login');
});

/*
 * Login - post
 */
Mike::post('/login', function() {
    session_start();

    $users = $GLOBALS['users'];
    if (array_key_exists($_REQUEST['name'], $GLOBALS['users'])) {
            $md5_password = $users[$_REQUEST['name']];
            if ($md5_password == md5($_REQUEST['password'])) {
                    $_SESSION['username'] = $_REQUEST['name'];
                    header('Location: /mike.php/list');
            }
    } 

    Mike::render($GLOBALS['layout'], $GLOBALS['login_page'], 
    ['styles' => ['css/style.css'], 
    'title' => 'Login - Mike.php'
    ]);
});

/*
 * Get directory listing
 */
Mike::get('/list', function() {
    session_start();

    if (!isset($_SESSION['username'])) {
            // user not logged in..
            header('Location: /mike.php/login');
    } else {

            // list the files
            $current_dir_path = dirname(__FILE__);
            $allowed_filetypes = ['png','jpg','gif','htm','html','php','php5'];
            $disallowed_files = ['mike.php','.','..'];

            $filetable = [];
            $filetable[] = '<ul id="filetable">';
            foreach (new DirectoryIterator($current_dir_path) as $fn) {
                    $filename = $fn->getFilename();
                    if (in_array(strtolower($filename),$disallowed_files) == false) {
                            if (in_array($fn->getExtension(), $allowed_filetypes)) {
                                    $filetable[] = '<li class="file" data-file="'.$fn->getFilename().'">'.$fn->getFilename().'</li>';
                            }
                    }
            }
            $filetable[] = '</ul>';

            // render the page.
            $page = $GLOBALS['file_page'];
            $page = str_replace("{{TABLE}}", implode($filetable), $page);
            $page = str_replace("{{FILEMGR_JS}}", $GLOBALS['filemgr_js'], $page);

            Mike::render($GLOBALS['layout'], $page,
                    ['styles' => ['css/style.css','css/filemgr.css'], 
                    'scripts' => ['js/140medley.js'],
                    'title' => 'File Manager - Mike.php'
                    ]);
    }
});

/*
 * Gets the directory structure as JSON
 */
Mike::get('/filelist.json', function() {
    
    
    session_start();

    if (!isset($_SESSION['username'])) {
        // user not logged in..
        header('Location: /mike.php/login');
    } else {
        Mike::set_content_type('json');
            // list the files
            $current_dir_path = dirname(__FILE__);
            $allowed_filetypes = ['png','jpg','gif','htm','html','php','php5'];
            $disallowed_files = ['mike.php','.','..'];

        $files = [];
        foreach (new DirectoryIterator($current_dir_path) as $fn) {
            $filename = $fn->getFilename();
            if (in_array(strtolower($filename),$disallowed_files) == false) {
                if (in_array($fn->getExtension(), $allowed_filetypes)) {
                    $files[] = $filename;        
                }
            }
        }

        $dir = array('/' => $files);

        echo json_encode($dir);
    }
});

/*
 * Get single file
 */
Mike::post('/file', function() {
	$filename = dirname(__FILE__) . '/' . $_POST['file'];
	$fp = fopen($filename, 'rb');

	// send the right headers
	header("Content-Type: text/plain");
	header("Content-Length: " . filesize($filename));
	fpassthru($fp);

	//http_send_content_type("text/plain");
	//http_send_file(dirname(__FILE__) . '/' .$filename);
});

/*
 * 404
 */
if (!Mike::$isRouteProcessed) {
    $content = str_replace("{{CONTENT}}", "Page not found", $GLOBALS['page_404']);
    
    Mike::render($GLOBALS['layout'], $content, 
        ['styles' => ['css/style.css'], 
        'title' => 'Mike.php',
        'headers' => ['Cache-Control: no-cache, no-store, must-revalidate','Pragma: no-cache','Expires: 0']
            ]);
}
