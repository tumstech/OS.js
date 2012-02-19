<?php
/*!
 * @file
 * OS.js - JavaScript Operating System - Session.class.php
 *
 * Copyright (c) 2011, Anders Evenrud
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 * 
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution. 
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @created 2012-02-19
 */

/**
 * Session -- Application User Session Class
 *
 * @author  Anders Evenrud <andersevenrud@gmail.com>
 * @package OSjs.Sources.Core
 * @see     DBObject
 * @class
 */
class Session extends DBObject {
  public static $Table = "session";
  public static $Columns = Array(
    "id"            => "int",
    "user_id"       => "int",
    "session_name"  => "str",
    "session_config"  => "str",
    "created_at"    => "date",
    "modified_at"   => "date"
  );

  public static function getBySnapshot($uid, $name) {
    return self::getByColumn(null, null, null, Array("user_id" => $uid, "session_name" => $name), 1);
  }
}

?>
