# P.A.S. Fork v. 1.2
<details>
  <summary>Preamble</summary>
<br/>
  
>For improvements was chosen a ready-made and well-tested webshell with a GUI interface.
**WSO2** is obsolete and requires significant improvements. **b374k** is too overloaded with unnecessary functionality. **P.A.S.**, with its structure and design, fit perfectly.
Although the author stopped supporting his product, I did not dare to release a modification with a further version number (the cobbler should stick to his last). Therefore, I offer my respect to @profexer, and I hope that he will continue his wonderful work someday.

<br/>
</details>

A modified version of the well-known webshell - P.A.S. by Profexer ([0](https://krebsonsecurity.com/2017/08/blowing-the-whistle-on-bad-attribution/), [1](https://github.com/winstrool/pas-4.1.1b_source_code), [2](https://github.com/wordfence/grizzly/tree/master/pas-4.1.1b)). Tries to solve the problem of detecting some requests and responses by various **Web Application Firewalls** and **Intrusion Detection Systems**. In most cases, such detections entail retaliatory measures from the attacked side, which is not always permissible during penetration tests and in red teaming.

```diff
- This tool is for educational and testing purposes only and is not
- intended to be put into practise unless you have authorized access to the system
+ Before using, it's better to remove all HttpOnly cookies for the domain
```

<br/>

Features of the original **P.A.S.**:
<details>
  <summary>Click to expand</summary>
  
## **General**

* Works on PHP >= 4.1.0
* Doesn't use PHP sessions or store any data on a server
* Uses asynchronous requests like a AJAX
* Can use POST or GET request method
* Can obfuscate requests
* Can work in custom environment (aka SUID mode)
* Supports 22 different charsets
* Encrypts the source code with your key (password) at download
* Resulting file doesn't contain encryption key (password) in any form
* Has stealth mode
* Working with different tasks without reload page and losing data
* Can be switched from fixed to flexible view
* Keyboard-only compatibility
* Has message log
* Shows server time

## **File Manager**

* Can upload several files at once
* Can create file, directory, symbolic and hard link
* Can change files properties (path, modified date, permission, owner, group)
* Can download files
* Can delete files
* Has files buffer:
  * mark, unmark, show marked files;
  * copy, move files from buffer to the current dir;
  * download files from buffer;
  * clear buffer;
* Can search files:
  * in several paths;
  * with limited depth;
  * by name with wildcard and case-sensitive options;
  * by type (file, directory);
  * by mode (readable, writable, full access);
  * with SUID attribute;
  * by owner IDs with definition of intervals;
  * by group IDs with definition of intervals;
  * by created date with definition of intervals;
  * by modified date with definition of intervals;
  * by size with definition of intervals;
  * by specified text with regex and case-sensitive options;
* Can save file with specified end of line
* Fast change properties, download and delete specified file
* Has breadcrumbs
* Click on extension cell to copy file name
* Press **ESC** to close current dialog
* Press **Alt+T** to switch between opened dialogs

## **SQL Client**

* DB support:
  * MySQL (mysql, mysqli, PDO)
  * MSSQL (mssql, sqlsrv, PDO, PDO SQLSRV, PDO DBLIB, PDO ODBC)
  * PgSQL (pg, PDO)
* Tree view of database schema
* Shows column data types
* Can show only selected columns data
* Can show tables row count
* Can reload single base/scheme/table schema
* Can dump multiple tables/schemes/bases
* Can dump only selected schemes/tables/columns
* Can dump to SQL or CSV format
* Has pagination for some database types

## **PHP Console**

* Isolates the results HTML code from the main page
* Can be switched from vertical to horizontal composition
* Press **Ctrl+Enter** to evaluate code

## **Terminal**

* Can execute commands via specified command processor
* Can execute commands via specified function
* Type **?** to show help
* Has command history:
  * type **history [N]** to show command history, where optional parameter N is number of last commands;
  * press **Up** & **Down** keys to navigate from command history;
  * type **![N]** to execute command, where N is:
     * ! to execute the last command;
     * N>0 to execute command #N from the command histroy;
     * N<0 to execute command #N from the end of the previous command;
* Can create system report (type **report ?** to more info)
* Can run Socks5 server:
  * throught Perl (type **socks5.perl** to more info);
  * throught Python (type **socks5.python** to more info);
* Can bind port:
  * throught Perl (type **bindport.perl** to more info);
  * throught Python (type **bindport.python** to more info);

* Can back connect:
  * throught Perl (type **backconnect.perl** to more info);
  * throught Python (type **backconnect.python** to more info);

* Type **cls** or **clear** or press **CTRL+L** to clear output
</details>

<br/>

**P.A.S. Fork** changes:
  
- Work via GET requests (parameters in cookies)
- Automatic switching to POST (with cancellation)
- Obfuscation of query keys and values
- Obfuscation of uploaded files
- Obfuscation of response
- Authorization by password
- Authorization by HTTP header (user-agent by default)
- MySQL dump fix in PDO mode
- Renamed "PHP 4-style constructors"
- Removed **pcntl_exec**
- New initialization logic (ini_*)
- **opcache_invalidate** after saving the file
- Dark color mode
- Built-in [Ace](https://github.com/ajaxorg/ace) code editor (loaded on demand)
- Added file extensions in filenames
- Option to display **ctime** (to find malicious files)
- Option to invert terminal output
- Removed startup "execs"
- FileManager JS crash fix (on rare envs)
- Reload file bug fix
- XHR instead IFRAME communication by default
- The client referrer is not sent
- Removed **X-Content-Type-Options** header in responses
- **Clear output** in **PHP Console** checked by default
- Option to set default tab on startup
- Built-in [safemode](https://github.com/cr1f/safemode/) script
- File sorting (Name, Ext, Size, etc)
- Sort by filename by default
- Reading **.gz** files (not saving)
- **Show as HTML** fix in **PHP Console**
- Maximize file editor window on double click
- Restoring minimized window position
- File reload interval (right click)
- Load default **favicon.ico** if exists
- Removed **expect** from exec's
- Supported PHP versions: 5 >= 5.1.2, 7, 8
  
<br/>

Screenshots
===========

**"ls -la;cat /etc/passwd"**:

* Request:

![request](https://i.imgur.com/24yso3q.png)

* Response:

![response](https://i.imgur.com/dfg880h.png)


**Dark mode:**

![dark mode](https://i.imgur.com/2mO7MLS.png)

**Code Editor:**
  
![dark mode](https://i.imgur.com/gAZkFMT.png)
  
<br/>

Troubleshooting
===============
**The script doesn't work and keeps returning the same response.**
* Perhaps caching of GET requests is enabled on the server. The solution is to turn off params passing via cookies and use POST requests (`$GLOBALS['COOKIE'] = false;`).

**The randomly repeated password prompts.**
* Most likely, your IP address changes with the same frequency. If so, then you need to change `$GLOBALS['REMOTE_ADDR']` to `false`.

**Large files not downloading.**
* The file wrapping operation happens on the fly, so a lot of RAM is required. The solution is to disable obfuscation by checking the `Skip response encoding` option in script GUI settings.

**How to remove the warning about the limit of request?**
* You should disable data transmission via cookies in the GUI (`Use cookie to request`). Or thus: `$GLOBALS['COOKIE'] = false;`.

**How set an authorization by the header?**
* `$GLOBALS['SECHEAD'] = 'SECRET_9CA2100C44E50D81BB7E3EED84AF43F4';` and append it for each request in your browser (`Secret-9ca2100c44e50d81bb7e3eed84af43f4: foobar`). Without password asking - `$GLOBALS['PASSHASH'] = '';`

**Switching the color theme is annoying.**
* `$GLOBALS['DARK'] = true;`

**How to configure the code editor?**
* You can place the editor [source code](https://github.com/ajaxorg/ace) on your host and specify `URL`. `MODE` and `THEME` is used to set default values during editor initialization. To exclude the editor completely, set the `$GLOBALS['ACECONF']` variable to `array()`. Set `DEFAULT` to `true` if you want to load the editor automatically.

**UI elements are too small.**
* **Ctrl** and **+** for zoom / **Ctrl** and **-** for zoom out / **Ctrl** + **0** for reset

**How to remove the password prompt?**
* `$GLOBALS['PASSHASH'] = '';`

**How to change the default tab?**
* You have 5 options: `tabFM`, `tabSQL`, `tabPHP`, `tabTrm`, `tabInf`. For example: `$GLOBALS['DEFAULT_TAB'] = 'tabFM';`, to start script from the **File Manager** tab. 

**`Send as` and `Load as` encodings don't work.**
* Currently, for functionality, you should disable request and response obfuscation in the GUI.
<br/>

Packer
============
**Usage:**

* `php packer.php pas_fork.php PHAR CM`
  
**Modes:**

* **ASCII** // `eval` 
* **ASCII CF** // `create_function`, PHP5/7 only
* **PHAR** // `include` + phar:// + PHAR container
* **PHAR CM** // the same compressed
* **ZIP** // `include` + phar:// + ZIP container
* **ZIP CM** // the same compressed ( *the output isn't always valid for PHP5/7, try to pack several times* )

The **php-zip** extension is not required for the packaged script to work on target host.

<br/>

Links
=====

* Official thread on the forum: https://antichat.com/threads/474941/
* Review from Sucuri: https://blog.sucuri.net/2020/10/p-a-s-fork-v-1-0-a-web-shell-revival.html
