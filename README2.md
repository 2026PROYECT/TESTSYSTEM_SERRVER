cls
en el php 
extension=sodium
extension=zip
extension=zip

folder using command
   `cd quiz-score`

3. Then install required libraries using (PHP 8.2 required)
   `composer install`


   
npm install 
npm install vite@5.4.21 --save-dev
npm install xlsx --legacy-peer-deps
php artisan key:generate
php artisan migrate:fresh --seed

 npm audit fix
npm run dev
```

8. Then open another terminal and run this command

```
php artisan serve
```
php artisan storage:link
Remove-Item (Get-PSReadLineOption).HistorySavePath
powershell
# Guarda este archivo como git-audimag.ps1 en tu proyecto
# Ejecútalo desde PowerShell dentro de la carpeta del repo

# Limpiar caché de storage para que Git reevalúe .gitignore
git rm -r --cached storage

# Agregar aud_imag y todo su contenido (forzado si estaba ignorado)
git add -f storage/app/public/aud_imag

# Commit automático con mensaje estándar
git commit -m "Actualizando aud_imag y subcarpetas"

# Push al remoto origin/main
git push -u origin main
Cómo usarlo
Copia el bloque en un archivo llamado git-audimag.ps1 dentro de tu proyecto.

Abre PowerShell en la carpeta del repo.

Ejecuta:

powershell
.\git-audimag.ps1
💡 Si quieres que el script sea más flexible (ej. subir todo storage/app/public y no solo aud_imag), cambia la línea:

powershell
git add -f storage/app/public