1.3
---
- Load default **favicon.ico** if exists
- Restoring minimized window position
- File reload interval (right click)
- Small bug fixes and improvements


1.2
---
- Option to set default tab on startup
- Built-in [safemode](https://github.com/cr1f/safemode/) script
- File sorting (Name, Ext, Size, etc)
- Reading **.gz** files (not saving)
- **Show as HTML** fix in **PHP Console**
- Maximize file editor window on double click
- Removed **expect** from exec's
- Sort by filename by default
- Improved JS output obfuscation
- Option to load **AceJS** by default
- **Ctrl** + **S** to save file in code editor
- **Ctrl** + **E** to change line wrap
- Editor hotkey hint
- Small bug fixes and improvements


1.1
---
- Built-in [Ace](https://github.com/ajaxorg/ace) code editor (loaded on demand)
- New initialization logic (ini_*)
- The client referrer is not sent
- XHR instead IFRAME communication by default
- Added file extensions in filenames
- Removed **X-Content-Type-Options** header in responses
- **Clear output** in **PHP Console** checked by default
- Reload file bug fix
- Added the "iframe instead of xhr" option in GUI
- Added the "Use cookie to request" option in GUI
- "Invert terminal output" moved to "Terminal" tab
- Some other small decorative changes

**packer.php**:

- eval() by default
- Not compressed ZIP
- Compressed PHAR
- Some randomizaion improvements


1.0
---
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
- FileManager JS crash fix (on rare envs)
- Reload file bug fix
- Supported PHP versions: 5 >= 5.1.2, 7, 8
