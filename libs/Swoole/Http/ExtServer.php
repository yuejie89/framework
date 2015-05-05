<?php
namespace Swoole\Http;

use Swoole;

/**
 * Class Http_LAMP
 * @package Swoole
 */
class ExtServer implements \Swoole\IFace\Http
{
    /**
     * @var \swoole_http_request
     */
    public $request;

    /**
     * @var \swoole_http_response
     */
    public $response;

    public $finish;
    public $document_root;

    protected $mimes;
    protected $types;

    static $gzip_extname = array('js' => true, 'css' => true, 'html' => true, 'txt' => true);

    function __construct()
    {
        $mimes = require LIBPATH . '/data/mimes.php';
        $this->mimes = $mimes;
        $this->types = array_flip($mimes);
    }

    function header($k, $v)
    {
        $k = ucwords($k);
        $this->response->header($k, $v);
    }

    function status($code)
    {
        $this->response->status($code);
    }

    function response($content)
    {
        $this->finish($content);
    }

    function redirect($url, $mode = 301)
    {
        $this->response->status($mode);
        $this->response->header('Location', $url);
    }

    function finish($content = null)
    {
        $this->finish = true;
        $this->response->write($content);
        throw new Swoole\ResponseException;
    }

    function setGlobal()
    {
        if (isset($this->request->get))
        {
            $_GET = $this->request->get;
        }
        else
        {
            $_GET = array();
        }
        if (isset($this->request->post))
        {
            $_POST = $this->request->post;
        }
        else
        {
            $_POST = array();
        }
        if (isset($this->request->files))
        {
            $_FILES = $this->request->files;
        }
        else
        {
            $_FILES = array();
        }
        if (isset($this->request->cookie))
        {
            $_COOKIE = $this->request->cookie;
        }
        else
        {
            $_COOKIE = array();
        }
        if (isset($this->request->server))
        {
            $_SERVER = $this->request->server;
        }
        else
        {
            $_SERVER = array();
        }
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
        $_SERVER['REQUEST_URI'] = $this->request->server['request_uri'];
        /**
         * 将HTTP头信息赋值给$_SERVER超全局变量
         */
        foreach($this->request->header as $key => $value)
        {
            $_key = 'HTTP_'.strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
        $_SERVER['REMOTE_ADDR'] = $this->request->server['remote_addr'];
    }

    function doStatic(\swoole_http_request $req, \swoole_http_response $resp)
    {
        $file = $this->document_root . $req->server['request_uri'];
        $extname = Swoole\Upload::getFileExt($file);
        if (empty($this->types[$extname]))
        {
            $mime_type = 'text/html';
        }
        else
        {
            $mime_type = $this->types[$extname];
        }
        if (isset(self::$gzip_extname[$extname]))
        {
            $resp->gzip();
        }
        $resp->header('Content-Type', $mime_type);
        $resp->end(file_get_contents($this->document_root . $req->server['request_uri']));
    }
}