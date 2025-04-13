@echo off
REM 
"C:\xampp\php\php.exe" "C:\xampp\htdocs\CitySmilesRepo\old\purge_old_records.php"
echo Purge completed at %date% %time% >> "C:\xampp\htdocs\CitySmilesRepo\old\purge_run.log"