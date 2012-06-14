<?php
/*!
 * @file
 * OS.js - JavaScript Operating System - Compiler.class.php
 *
 * Copyright (c) 2011-2012, Anders Evenrud <andersevenrud@gmail.com>
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
 * @author Anders Evenrud <andersevenrud@gmail.com>
 * @licence Simplified BSD License
 * @created 2012-01-26
 */

require_once "Glade.class.php";

/**
 * Compiler -- Application Compiler main Class
 *
 * @author  Anders Evenrud <andersevenrud@gmail.com>
 * @package OSjs.Libraries
 * @class
 */
class Compiler
{
  /////////////////////////////////////////////////////////////////////////////
  // VARIABLES
  /////////////////////////////////////////////////////////////////////////////

  private $_oDocument;                    //!< XML Document
  private $_oRoot;                        //!< XML Document Root node

  public static $TemplatePHP;             //!< text/plain PHP Template
  public static $TemplateCSS;             //!< text/plain CSS Template
  public static $TemplateJS;              //!< text/plain Application JS Template
  public static $TemplateJSPI;            //!< text/plain PanelItem JS Template
  public static $TemplateJSBS;            //!< text/plain BackgroundService JS Template
  public static $TemplateJSWindow;        //!< text/plain Glade Template

  /////////////////////////////////////////////////////////////////////////////
  // MAGICS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * @constructor
   */
  protected function __construct() {
  }

  /////////////////////////////////////////////////////////////////////////////
  // METHODS
  /////////////////////////////////////////////////////////////////////////////

  /**
   * Compile a PanelItem by Metadata file
   * @param   String    $class_name         Class name
   * @param   String    $metadata_path      Metadata XML file path
   * @param   bool      $dry_run            Dry-run (Default = false)
   * @return Mixed
   */
  protected function compilePanelItem($class_name, $metadata_path, $dry_run = false) {
    if ( $xml = new SimpleXmlElement(file_get_contents($metadata_path)) ) {
      print "Compiling from '$metadata_path'\n";

      // Generic variables
      $project_name         = ((string) $xml['name']);
      $project_enabled      = true;
      $project_title        = PanelItem::PANELITEM_TITLE;
      $project_titles       = Array();
      $project_icon         = PanelItem::PANELITEM_TITLE;
      $project_desc         = PanelItem::PANELITEM_DESC;
      $project_descs        = Array();
      $timestamp            = strftime("%F");
      $js_linguas           = Array(DEFAULT_LANGUAGE => Array());

      $out_css      = PATH_BUILD . "/apps/{$class_name}.css";
      $out_php      = PATH_BUILD . "/apps/{$class_name}.class.php";
      $out_js       = PATH_BUILD . "/apps/{$class_name}.js";

      // Parse general attributes
      foreach ( $xml->property as $p ) {
        $val = ((string) $p);
        switch ( $p['name'] ) {
          case "enabled" :
            if ( $val == "false" ) {
              $project_enabled = false;
              break;
            }
            break;
          case "title" :
            if ( $val ) {
              if ( isset($p['language']) && !empty($p['language']) ) {
                $lang = ((string)$p['language']);
                $project_titles[$lang] = $val;
                if ( $lang == DEFAULT_LANGUAGE ) {
                  $project_title = $val;
                }
              } else {
                $project_tiles[DEFAULT_LANGUAGE] = $val;
                $project_title = $val;
              }
            }
            break;
          case "icon" :
            if ( $val ) {
              $project_icon = $val;
            }
            break;
        }
      }

      // Skip application
      if ( ENV_PRODUCTION ) {
        if ( !$project_enabled ) {
          print "\tNot enabled...skipping...!\n";
          return -1;
        }
      }

      // ...
      if ( !isset($project_titles[DEFAULT_LANGUAGE]) ) {
        $project_titles[DEFAULT_LANGUAGE] = $project_title;
      }
      foreach ( $project_titles as $tk => $tv ) {
        $js_linguas[$tk]["title"] = $tv;
      }
      $js_linguas[DEFAULT_LANGUAGE]["title"] = $project_title;

      $js_linguas     = json_encode($js_linguas);

      // Generate files
      $rep_php = Array(
        "%PACKAGETYPE%"   => "PanelItem", //FIXME
        "%CLASSNAME%"     => $class_name,
        "%TIMESTAMP%"     => $timestamp
      );
      $rep_css = Array(
        "%PACKAGETYPE%"   => "PanelItem", //FIXME
        "%CLASSNAME%"     => $class_name
      );
      $rep_js = Array(
        "%CLASSNAME%"         => $class_name,
        "%LINGUAS%"           => $js_linguas,
        "%DEFAULT_LANGUAGE%"  => DEFAULT_LANGUAGE,
      );

      $content_css  = str_replace(array_keys($rep_css),
                                  array_values($rep_css),
                                  self::$TemplateCSS);
      $content_php  = str_replace(array_keys($rep_php),
                                  array_values($rep_php),
                                  self::$TemplatePHP);
      $content_js   = str_replace(array_keys($rep_js),
                                  array_values($rep_js),
                                  self::$TemplateJSPI);


      if ( !$dry_run ) {
        file_put_contents($out_php,   $content_php);
        file_put_contents($out_css,   $content_css);
        file_put_contents($out_js,    $content_js);
      }

      print sprintf("\tDONE [%s]...\n", implode(",", Array("css", "php", "js")));

      return true;
    }

    return false;
  }

  /**
   * Compile a BackgroundService by Metadata file
   * @param   String    $class_name         Class name
   * @param   String    $metadata_path      Metadata XML file path
   * @param   bool      $dry_run            Dry-run (Default = false)
   * @return Mixed
   */
  protected function compileService($class_name, $metadata_path, $dry_run = false) {
    if ( $xml = new SimpleXmlElement(file_get_contents($metadata_path)) ) {
      print "Compiling from '$metadata_path'\n";
      // TODO
      print "\tDONE!\n";

      return true;
    }

    return false;
  }

  /**
   * Compile a project by Metadata file
   * @param   String    $class_name         Class name
   * @param   String    $metadata_path      Metadata XML file path
   * @param   bool      $dry_run            Dry-run (Default = false)
   * @return  Mixed
   */
  protected function compileProject($class_name, $metadata_path, $dry_run = false) {
    if ( $xml = new SimpleXmlElement(file_get_contents($metadata_path)) ) {
      print "Compiling from '$metadata_path'\n";

      // Generic variables
      $project_name         = ((string) $xml['name']);
      $project_enabled      = true;
      $project_title        = Application::APPLICATION_TITLE;
      $project_titles       = Array();
      $project_icon         = Application::APPLICATION_ICON;
      $project_compability  = Array();
      $project_mimes        = Array();
      $timestamp            = strftime("%F");

      // Paths
      $out_css      = PATH_BUILD . "/apps/{$class_name}.css";
      $out_php      = PATH_BUILD . "/apps/{$class_name}.class.php";
      $out_js       = PATH_BUILD . "/apps/{$class_name}.js";
      $out_html     = PATH_BUILD . "/apps/{$class_name}.html";
      $schema_path  = null;

      if ( ($schema = ((string) $xml['schema'])) ) {
        $schema_path = str_replace("metadata.xml", $schema, $metadata_path);
        if ( !file_exists($schema_path) ) {
          $schema_path = null;
        }
      }

      // Temporary variables
      $temp_windows   = Array();
      $glade_windows  = Array();
      $glade_html     = Array();
      $js_prepend     = "";
      $js_append      = "";
      $js_compability = "";
      $js_glade       = "";
      $js_root_window = "";
      $js_linguas     = Array(DEFAULT_LANGUAGE => Array());

      // Parse general attributes
      foreach ( $xml->property as $p ) {
        $val = ((string) $p);
        switch ( $p['name'] ) {
          case "enabled" :
            if ( $val == "false" ) {
              $project_enabled = false;
              break;
            }
            break;
          case "title" :
            if ( $val ) {
              if ( isset($p['language']) && !empty($p['language']) ) {
                $lang = ((string)$p['language']);
                $project_titles[$lang] = $val;
                if ( $lang == DEFAULT_LANGUAGE ) {
                  $project_title = $val;
                }
              } else {
                $project_tiles[DEFAULT_LANGUAGE] = $val;
                $project_title = $val;
              }
            }
            break;
          case "icon" :
            if ( $val ) {
              $project_icon = $val;
            }
            break;
        }
      }

      if ( !isset($project_titles[DEFAULT_LANGUAGE]) ) {
        $project_titles[DEFAULT_LANGUAGE] = $project_title;
      }

      // Skip application
      if ( ENV_PRODUCTION ) {
        if ( !$project_enabled ) {
          print "\tNot enabled...skipping...!\n";
          return -1;
        }
      }

      // Parse other nodes
      if ( isset($xml->compability) ) {
        foreach ( $xml->compability as $c ) {
          $project_compability[] = (string) $c;
        }
      }

      if ( isset($xml->mime) ) {
        foreach ( $xml->mime as $m ) {
          $project_mimes[] = (string) $m;
        }
      }

      // Generate code for Glade Schema
      if ( $schema_path ) {
        if ( $glade = Glade::parse($schema_path) ) {
          print "\tParsing Glade...\n";
          if ( $result = self::_parseGlade($glade, $project_mimes, $project_title, $project_icon) ) {
            extract($result, EXTR_OVERWRITE);

            $temp_windows[$window_id] = $window_properties;
            unset($window_id);
            unset($window_properties);
          }
        }
      }

      // ...
      foreach ( $project_titles as $tk => $tv ) {
        $js_linguas[$tk]["title"] = $tv;
      }
      $js_linguas[DEFAULT_LANGUAGE]["title"] = $project_title;

      // Generate files
      $js_compability = json_encode($project_compability);
      $js_linguas     = json_encode($js_linguas);
      $js_glade       = implode("\n", $glade_windows);

      $rep_php = Array(
        "%PACKAGETYPE%"   => "Application", //FIXME
        "%CLASSNAME%"     => $class_name,
        "%TIMESTAMP%"     => $timestamp
      );
      $rep_css = Array(
        "%PACKAGETYPE%"   => "Application", //FIXME
        "%CLASSNAME%"     => $class_name
      );
      $rep_js = Array(
        "%PACKAGETYPE%"       => "Application", //FIXME
        "%CLASSNAME%"         => $class_name,
        "%COMPABILITY%"       => $js_compability,
        "%CODE_GLADE%"        => $js_glade,
        "%CODE_PREPEND%"      => $js_prepend,
        "%CODE_APPEND%"       => $js_append,
        "%ROOT_WINDOW%"       => $js_root_window,
        "%LINGUAS%"           => $js_linguas,
        "%DEFAULT_LANGUAGE%"  => DEFAULT_LANGUAGE
      );

      $content_css  = str_replace(array_keys($rep_css), array_values($rep_css), self::$TemplateCSS);
      $content_php  = str_replace(array_keys($rep_php), array_values($rep_php), self::$TemplatePHP);
      $content_js   = str_replace(array_keys($rep_js), array_values($rep_js), self::$TemplateJS);
      $content_html = implode("\n", $glade_html);

      // Write data
      if ( !$dry_run ) {
        file_put_contents($out_php,   $content_php);
        file_put_contents($out_css,   $content_css);
        file_put_contents($out_js,    $content_js);
        file_put_contents($out_html,  $content_html);
      }

      print sprintf("\tDONE [%s]...\n", implode(",", Array("css", "php", "js", "html")));

      return true;
    }

    return false;
  }

  /////////////////////////////////////////////////////////////////////////////
  // PUBLIC METHODS
  /////////////////////////////////////////////////////////////////////////////

  public static function compile($project_name, $dry_run = false, $root = null) {
    $root = ($root ? $root : PATH_PACKAGES);
    $compiler = new self();

    $path = "{$root}/{$project_name}/metadata.xml";
    if ( file_exists($path) ) {
      if ( preg_match("/^(Application|System)(.*)$/", $project_name) ) {
        return $compiler->compileProject($project_name, $path, $dry_run);
      } else if ( preg_match("/^PanelItem(.*)$/", $project_name) ) {
        return $compiler->compilePanelItem($project_name, $path, $dry_run);
      } else if ( preg_match("/^Service(.*)$/", $project_name) ) {
        return $compiler->compileService($project_name, $path, $dry_run);
      }
    }

    return false;
  }

  /**
   * Compile all Applications found in folder
   * @param   bool      $dry_run  Dry-run (Default = false)
   * @param   String    $root     Search folder (Default = called script)
   * @return  bool
   */
  public static function compileAll($dry_run = false, $root = null) {
    $root   = ($root ? $root : PATH_PACKAGES);

    if ( $dh  = opendir($root) ) {
      $compiler = new self();

      while (false !== ($filename = readdir($dh))) {
        $path = "{$root}/{$filename}/metadata.xml";
        if ( file_exists($path) ) {
          if ( ($xml = file_get_contents($path)) && ($data = new SimpleXmlElement($xml)) ) {
            switch ( ((string) $data['type']) ) {
              case "Application" :
                $compiler->compileProject($filename, $path, $dry_run);
              break;
              case "PanelItem" :
                $compiler->compilePanelItem($filename, $path, $dry_run);
              break;
              case "Service" :
              case "BackgroundService" :
                $compiler->compileService($filename, $path, $dry_run);
              break;
            }
          }
        }
      }

      return true;
    }

    return false;
  }

  /**
   * Parse a Glade file
   * @param   Glade   $glade              Glade Class instance
   * @param   Array   $mimes              Project MIMEs
   * @param   String  $project_title      Project Title
   * @param   String  $project_icon       Project Icon
   * @return  Array
   */
  protected static function _parseGlade(Glade $glade, Array $project_mimes, $project_title, $project_icon) {
    $mimes              = json_encode($project_mimes);
    $js_prepend         = "";
    $js_append          = "";
    $js_root_window     = "";
    $code_init          = "";
    $code_class         = "";
    $code_create        = "";
    $window_properties  = Array();
    $window_id          = "";
    $glade_html         = Array();
    $glade_windows      = Array();
    $found_root         = false;

    if ( $windows = $glade->getWindows() ) {
      foreach ( $windows as $id => $window ) {
        // Property overrides
        if ( !$window['properties']['title'] ) {
          $window['properties']['title'] = $project_title;
        }
        if ( !$window['properties']['icon'] ) {
          $window['properties']['icon'] = $project_icon;
        }

        $window_properties = $window['properties'];
        $window_id         = $id;

        foreach ( $window['properties'] as $pk => $pv ) {
          if ( in_array($pk, Array("type")) ) {
            continue;
          }

          if ( $pk == "title" ) {
            $pv = "LABELS.title";
          } else {
            if ( is_bool($pv) ) {
              $pv = $pv ? 'true' : 'false';
            } else {
              if ( !$pv ) {
                $pv = "null";
              } else {
                if ( !is_numeric($pv) ) {
                  $pv = "'{$pv}'";
                }
              }
            }
          }

          $code_init .= "        this._{$pk} = $pv;\n";
        }

        foreach ( $window['signals'] as $obj => $evs ) {
          foreach ( $evs as $ev_type => $ev_handler ) {
            $ev_code = "";

            if ( !preg_match("/^Event/", $ev_handler) ) {
              $ev_handler = "Event{$ev_handler}";
            }

if ( $ev_handler == "EventMenuOpen" ) {
  $ev_code = <<<EOJAVASCRIPT

        var my_mimes    = {$mimes};
        var my_callback = function(fname) {}; // FIXME
        var cur         = (argv && argv['path'] ? argv['path'] : null);

        this.app.defaultFileOpen(function(fname) {
          my_callback(fname);
        }, mu_mimes, null, cur);
EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuSave" ) {
              $ev_code = <<<EOJAVASCRIPT

        var my_filename = (argv && argv['path'] ? argv['path'] : null);
        var my_content  = ""; // FIXME
        var my_mimes    = {$mimes};
        var my_callback = function(fname) {}; // FIXME

        if ( my_filename ) {
          this.app.defaultFileSave(my_filename, my_content, function(fname) {
            my_callback(fname);
          }, my_mimes, undefined, false);
        }

EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuSaveAs" ) {
              $ev_code = <<<EOJAVASCRIPT

        var my_filename = (argv && argv['path'] ? argv['path'] : null);
        var my_content  = ""; // FIXME
        var my_mimes    = {$mimes};
        var my_callback = function(fname, fmime) {}; // FIXME

        this.app.defaultFileSave(my_filename, my_content, function(fname) {
          my_callback(fname);
        }, my_mimes, undefined, true);

EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuClose" || $ev_handler == "EventMenuQuit" || $ev_handler == "EventClose" || $ev_handler == "EventQuit" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.\$element.find(".ActionClose").click();

EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuTextCopy" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.app._clipboard("copy");
EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuTextPaste" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.app._clipboard("paste");
EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuTextCut" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.app._clipboard("cut");
EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuTextSelectAll" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.app._clipboard("select");
EOJAVASCRIPT;
            } else if ( $ev_handler == "EventMenuTextDelete" ) {
              $ev_code = <<<EOJAVASCRIPT

        this.app._clipboard("delete");
EOJAVASCRIPT;
            } else {
              if ( $ev_type == "file-set" ) {
                $ev_code = <<<EOJAVASCRIPT

        var my_mimes    = {$mimes};
        var my_path     = self.\$element.find(".{$obj} input[type=text]").val();

        this.app.createFileDialog(function(fname) {
          self.\$element.find(".{$obj} input[type=text]").val(fname);
          self.\$element.find(".{$obj} input[type=hidden]").val(fname);
        }, my_mimes, "open", dirname(my_path));

EOJAVASCRIPT;
              }
            }

            $code_class .= <<<EOJAVASCRIPT

      {$ev_handler} : function(el, ev) {
        var self = this;

      {$ev_code}
      },


EOJAVASCRIPT;

            if ( $ev_type == "file-set" ) {
                  $code_create .= <<<EOJAVASCRIPT

          el.find(".{$obj} button").click(function(ev) {
            self.{$ev_handler}(this, ev);
          });

EOJAVASCRIPT;

            } else if ( $ev_type == "input-activate" ) {
                  $code_create .= <<<EOJAVASCRIPT

          el.find(".{$obj}").keypress(function(ev) {
            var k = ev.keyCode || ev.which;
            if ( k == 13 ) {
              self.{$ev_handler}(this, ev);
            }
          });

EOJAVASCRIPT;
            } else {

                  $code_create .= <<<EOJAVASCRIPT

          el.find(".{$obj}").{$ev_type}(function(ev) {
            self.{$ev_handler}(this, ev);
          });

EOJAVASCRIPT;
            }

          } // foreach
        } // foreach


        $replace = Array(
          "%WINDOW_NAME%" => $id,
          "%IS_DIALOG%"   => "false",
          "%CONTENT%"     => addslashes(str_replace("\n", "", preg_replace("/\s+/", " ", $window['content']))),
          "%CODE_INIT%"   => $code_init,
          "%CODE_CLASS%"  => $code_class,
          "%CODE_CREATE%" => $code_create
        );

        $glade_windows[$id] = str_replace(array_keys($replace), array_values($replace), self::$TemplateJSWindow);

        // First window is always root window
        if ( !$found_root ) {
          $js_root_window = "root_window";
          $js_prepend = <<<EOJAVASCRIPT
        var root_window = new Window_{$id}(self);
EOJAVASCRIPT;

          $js_append = <<<EOJAVASCRIPT
        root_window.show();
EOJAVASCRIPT;
          $found_root = true;
        }

        $glade_html[] = $window['content'];

      } // foreach
    }

    return Array(
      "js_prepend"        => $js_prepend,
      "js_append"         => $js_append,
      "js_root_window"    => $js_root_window,
      "glade_html"        => $glade_html,
      "glade_windows"     => $glade_windows,
      "window_properties" => $window_properties,
      "window_id"         => $window_id
    );

  }

}

///////////////////////////////////////////////////////////////////////////////
// TEMPLATES
///////////////////////////////////////////////////////////////////////////////

Compiler::$TemplatePHP      = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.php"));
Compiler::$TemplateCSS      = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.css"));
Compiler::$TemplateJS       = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.js"));
Compiler::$TemplateJSPI     = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.panelitem.js"));
Compiler::$TemplateJSBS     = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.service.js"));
Compiler::$TemplateJSWindow = file_get_contents(sprintf("%s/%s", PATH_TEMPLATES, "compiler.window.js"));

?>
