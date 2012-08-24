<?php
namespace Acme\AdminBoard\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class AdminBoardController extends Controller
{
  public function indexAction($name)
  {

 
       return new Response()'<html><body>Hello ' . $name. '!</body></html>';

  }

}

?>
