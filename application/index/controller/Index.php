<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{


    public function index()
    {
        return $this->fetch("login");
    }

    public function login(){
        $data = input("post.");
        $name = $data["name"];
        $avatar = $data["avatar"];
        $uid = $data["uid"];
        $this->assign(["name"=>$name,"avatar"=>$avatar,"uid"=>$uid,"user"=>$data]);
        return $this->fetch('index');
    }

}
