# P.A.S. Fork v. 1.0
<details>
  <summary>Preamble</summary>
<br/>
  
>For improvements was chosen a ready-made and well-tested webshell with a GUI interface.
**WSO2** is obsolete and requires significant improvements. **b374k** is too overloaded with unnecessary functionality. **P.A.S.**, with its structure and design, fit perfectly.
Although the author stopped supporting his product, I did not dare to release a modification with a further version number (the cobbler should stick to his last). Therefore, I offer my respect to @profexer, and I hope that he will continue his wonderful work someday...

<br/>
</details>

A modified version of the well-known webshell - P.A.S. by Profexer ([1](https://github.com/winstrool/pas-4.1.1b_source_code), [2](https://github.com/wordfence/grizzly/tree/master/pas-4.1.1b)). Tries to solve the problem of detecting some requests and responses by various **Web Application Firewalls** and **Intrusion Detection Systems**. In most cases, such detections entail retaliatory measures from the attacked side, which is not always permissible during penetration tests and in red teaming.

```diff
- This tool is for educational and testing purposes only and is not intended to be put into practise unless you have authorized access to the system
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
- **opcache_invalidate** after saving the file
- Dark color mode
- Option to display **ctime** (to find malicious files)
- Option to invert terminal output
- Removed startup "execs"
- FileManager JS crash fix (on rare envs)
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
  
<br/>

Packer modes
============

* **ASCII** // `create_function`, PHP5/7 only
* **ASCII PHP8** // `eval`
* **PHAR** // `include` + phar:// + PHAR container
* **ZIP** // `include` + phar:// + ZIP container

<br/>

Links
=====

* Official thread on the forum: https://antichat.com/threads/474941/
* Review from Sucury: https://blog.sucuri.net/2020/10/p-a-s-fork-v-1-0-a-web-shell-revival.html
