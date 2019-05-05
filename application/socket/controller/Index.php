<?php
namespace app\socket\controller;
use Workerman\Worker;
use Workerman\Autoloader;
class Index
{
    //测试
    public function index()
    {

        // 创建一个Worker监听2346端口，使用websocket协议通讯  
        $ws_worker = new Worker("websocket://0.0.0.0:2346");


        echo 'aa123a';
        // 启动4个进程对外提供服务  
        $ws_worker->count = 4;

        $ws_worker->onConnect = function($connection)
        {
            $connection->send('hello 123' );
        };

        // 当收到客户端发来的数据后返回hello $data给客户端  
        $ws_worker->onMessage = function($connection, $data)
        {
            // 向客户端发送hello $data  
            $connection->send('hello ' . $data);
        };

        // 运行worker  
        Worker::runAll();

    }

    //推送消息
    //参考http://doc.workerman.net/315238
    public function push(){

        // 初始化一个worker容器，监听1234端口
        $worker = new Worker('websocket://0.0.0.0:2346');
        // ====这里进程数必须必须必须设置为1====
        $worker->count = 1;
        // 新增加一个属性，用来保存uid到connection的映射(uid是用户id或者客户端唯一标识)
        $worker->uidConnections = array();

        $user_connections =[];

        // 当有客户端发来消息时执行的回调函数
        $worker->onMessage = function($connection, $data)
        {
            global $worker;

            global $user_connections;
            // 判断当前客户端是否已经验证,即是否设置了uid
            if(!isset($connection->uid))
            {
                // 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
                $connection->uid = $data;
                /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
                 * 实现针对特定uid推送数据
                 */
                $worker->uidConnections[$connection->uid] = $connection;

                $user_connections[$connection->uid]=["uid"=>$connection->uid];

                $this->broadcast($worker,"all:".json_encode($user_connections));
               return;

            }
            // 其它逻辑，针对某个uid发送 或者 全局广播
            // 假设消息格式为 uid:message 时是对 uid 发送 message
            // uid 为 all 时是全局广播
            list($recv_uid, $message) = explode(':', $data);
            // 全局广播
            if($recv_uid == 'all')
            {
                $this->broadcast($worker,"all:".$message);
            }
            // 给特定uid发送
            else
            {
                $this->sendMessageByUid($worker,$recv_uid, $connection->uid.":".$message);
            }
        };

        // 当有客户端连接断开时
        $worker->onClose = function($connection)
        {
            global $worker;
            global $user_connections;
            if(isset($connection->uid))
            {
                // 连接断开时删除映射
                unset($worker->uidConnections[$connection->uid]);

                unset($user_connections[$connection->uid]);

                $this->broadcast($worker,"all:".json_encode($user_connections));

            }
        };

        // 运行所有的worker（其实当前只定义了一个）
        Worker::runAll();
    }


    // 向所有验证的用户推送数据
    function broadcast($worker,$message)
    {
        //global $worker;
        foreach($worker->uidConnections as $connection)
        {
            $connection->send($message);
        }
    }

    // 针对uid推送数据
    function sendMessageByUid($worker,$uid, $message)
    {
        //global $worker;
        if(isset($worker->uidConnections[$uid]))
        {
            $connection = $worker->uidConnections[$uid];
            $connection->send($message);
        }
    }
}  
