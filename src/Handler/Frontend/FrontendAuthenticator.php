<?php
/**
 * Author: Louis Livi <574747417@qq.com>
 * Date: 2018/11/9
 * Time: 上午10:01
 */

namespace SMProxy\Handler\Frontend;

use SMProxy\MysqlPacket\HandshakePacket;
use SMProxy\MysqlPacket\Util\Capabilities;
use SMProxy\MysqlPacket\Util\CharsetUtil;
use SMProxy\MysqlPacket\Util\RandomUtil;
use SMProxy\MysqlPacket\Util\SecurityUtil;
use SMProxy\MysqlPacket\Util\Versions;

class FrontendAuthenticator
{
    public $seed = [];
    public $auth = false;
    public $database;

    public function getHandshakePacket($server_id)
    {
        $rand1 = RandomUtil::randomBytes(8);
        $rand2 = RandomUtil::randomBytes(12);
        $this->seed = array_merge($rand1, $rand2);
        $hs = new HandshakePacket();
        $hs->packetId = 0;
        $hs->protocolVersion = Versions::PROTOCOL_VERSION;
        $hs->serverVersion = getBytes(Versions::SERVER_VERSION);
        $hs->threadId = $server_id;
        $hs->seed = $rand1;
        $hs->serverCapabilities = $this->getServerCapabilities();
        $hs->serverCharsetIndex = (CharsetUtil::getIndex('utf8mb4') & 0xff);
        $hs->serverStatus = 2;
        $hs->restOfScrambleBuff = $rand2;
        return getString($hs->write());
    }

    public function checkPassword(array $password, String $pass)
    {
        // check null
        if ($pass == null || strlen($pass) == 0) {
            if ($password == null || count($password) == 0) {
                return true;
            } else {
                return false;
            }
        }
        if ($password == null || count($password) == 0) {
            return false;
        }

// encrypt
        $encryptPass = null;
        try {
            $encryptPass = SecurityUtil::scramble411($pass, $this->seed);
        } catch (\Exception $e) {
            return false;
        }
        if ($encryptPass != null && (count($encryptPass) == count($password))) {
            $i = count($encryptPass);
            while ($i-- != 0) {
                if ($encryptPass[$i] != $password[$i]) {
                    return false;
                }
            }
        } else {
            return false;
        }

        return true;
    }

    protected function getServerCapabilities()
    {
        $flag = 0;
        $flag |= Capabilities::CLIENT_LONG_PASSWORD;
        $flag |= Capabilities::CLIENT_FOUND_ROWS;
        $flag |= Capabilities::CLIENT_LONG_FLAG;
        $flag |= Capabilities::CLIENT_CONNECT_WITH_DB;
        // flag |= Capabilities::CLIENT_NO_SCHEMA;
        // flag |= Capabilities::CLIENT_COMPRESS;
        $flag |= Capabilities::CLIENT_ODBC;
        // flag |= Capabilities::CLIENT_LOCAL_FILES;
        $flag |= Capabilities::CLIENT_IGNORE_SPACE;
        $flag |= Capabilities::CLIENT_PROTOCOL_41;
        $flag |= Capabilities::CLIENT_INTERACTIVE;
        // flag |= Capabilities::CLIENT_SSL;
        $flag |= Capabilities::CLIENT_IGNORE_SIGPIPE;
        $flag |= Capabilities::CLIENT_TRANSACTIONS;
        // flag |= ServerDefs.CLIENT_RESERVED;
        $flag |= Capabilities::CLIENT_SECURE_CONNECTION;
        return $flag;
    }

protected function failure(int $errno, String $info) {

}
}