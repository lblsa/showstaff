<?php
namespace Acme\AdminBoard\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AdminBoardController extends Controller
{
  public function indexAction($name)
  {

 
       return new Responce()'<html><body>Hello ' . $name. '!</body></html>';

  }

}

?>
