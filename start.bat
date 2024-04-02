@echo off

set "source_folder=%cd%\initial"
set "destination_folder=%cd%\src"

set "not_first_file=%cd%\not_first"

if not exist "%not_first_file%" (
    echo Proje ilk defa çalıştırılıyor...
    
    echo Klasörler kopyalanıyor...
    mkdir "%destination_folder%\initial"
    xcopy "%source_folder%" "%destination_folder%\initial" /E /I /Y
    
    echo Docker-compose başlatılıyor...
    start docker-compose up --force-recreate --build
    
    timeout /t 5 /nobreak >nul
    
    echo Initial değerler yükleniyor...
    :wait_loop
    set response=
    for /f "delims=" %%a in ('powershell -command "(Invoke-WebRequest -Uri 'http://localhost:8080/initial/init.php').StatusCode"') do set "response=%%a"
    if not "%response%"=="200" (
        echo Initial değerleri eklemek için projenin başlaması bekleniyor...
        timeout /t 5 /nobreak >nul
        goto wait_loop
    )

    type nul > "%not_first_file%"
    echo Proje başarıyla başlatılmış, bu pencere 5 saniye içerisinde kapatılacak...
    timeout /t 5 /nobreak
) else (
    start docker-compose up
)
