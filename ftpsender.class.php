<?php

/* 
 * Copyright (C) 2014 Everton
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class FtpSender{
    
    /**
     *
     * @var integer Maximum number of attempts to send each package.
     */
    public $maxAttempt = 3;
    /**
     *
     * @var integer Size in bytes of each packege.
     */
    public $sizePackage = 1024;
    /**
     *
     * @var string Hash of the local file.
     */
    protected $hashLocal;
    /**
     *
     * @var string Hash of the remote file.
     */
    protected $hashRemote;
    /**
     *
     * @var integer Number of seconds to start sending after initial procedures and to start a new submission attempt.
     */
    public $timeSleep = 60;
    
    /**
     * 
     * @param string $local The path to the local file.
     * @param string $remote The path to the remote file as an FTP wrapper. For details, see {@link http://au2.php.net/manual/en/wrappers.ftp.php ftp://}
     * @return boolean
     * @throws Exception
     */
    public function send($local, $remote){
        //test if local file exists
        if(!file_exists($local)){
            throw new Exception("The file $local not exists.");
        }else{
            self::log("The local file is $local");
        }
        
        //opening files
        if($local_stream = fopen($local, 'r')){
            self::log("Local file opened.");
        }else{
            throw new Exception("Fail on opening local file.");
        }
        
        $opts = array('ftp' => array('overwrite' => true));
        $context = stream_context_create($opts);

        if($remote_stream = fopen($remote, 'w', false, $context)){
            self::log("Remote file opened.");
        }else{
            throw new Exception("Fail on open remote file.");
        }
        
        $nopack = ceil(filesize($local) / $this->sizePackage);
        
        self::log("Number estimated of packege is $nopack.");
        
        self::log("Send start in {$this->timeSleep} seconds.");
        sleep($this->timeSleep);
        
        //send
        $pack_counter = 1;
        while(!feof($local_stream)){
            $package = fread($local_stream, $this->sizePackage);
            
            for($attempt = 1; $attempt <= $this->maxAttempt; $attempt++){
                if($write = fwrite($remote_stream, $package)){
                    self::log("Package #$pack_counter sended on attempt #$attempt.");
                    $success = true;
                    break;
                }else{
                    @fclose($remote_stream);
                    sleep($this->timeSleep);
                    $remote_stream = fopen($remote, 'a');
                    $success = false;
                }
            }
            if($success == false){
                fclose($local_stream);
                fclose($remote_stream);
                throw new Exception("Fail to send file on package #$pack_counter. Aborting.");
            }
            $pack_counter++;
        }
        fclose($local_stream);
        fclose($remote_stream);
        self::log("Success on send file.");
        
        //test
        self::log("Testing send.");
        
        $this->hashLocal = md5_file($local);
        self::log("Hash for local file is {$this->hashLocal}");
        
        $this->hashRemote = md5_file($remote);
        self::log("Hash for remote file is {$this->hashRemote}");
        
        if($this->hashLocal == $this->hashRemote){
            self::log("Success in sending the file");
            return true;
        }else{
            throw new Exception("Fail in sending the file.");
        }
    }
    
    /**
     * Show log.
     * @param string $msg Message log.
     */
    protected static function log($msg){
        printf("%s\t%s", date('Y-m-d H:i:s'), $msg.PHP_EOL);
    }
}