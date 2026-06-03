## About Quiz Score

1. Laravel
2. Vue JS
3. Composition API - Vue JS
4. Tailwind CSS
5. Vite
6. SQLite/MySQL -- (set via .env)
7. PHP ^8.2 (must be installed)

## Getting Started Step by Step setup

en el php 
extension=sodium
extension=zip
extension=zip

folder using command
   `cd quiz-score`

3. Then install required libraries using (PHP 8.2 required)
   `composer install`

4. Then create a .env file and generate key for this project using command
   `cp .env.example .env`
npm install vite@5.4.21 --save-dev
npm install xlsx --legacy-peer-deps
``

5. Setup MYSQL 

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quiz_score
DB_USERNAME=root
DB_PASSWORD=
```

6. After connecting the db with project, then run command
   If you use sqlite, then you don't need to run command

```
php artisan migrate:fresh --seed
```

7. Running migrate and db-seed gives you 1 admin account to manage quiz and question and 2 student account to attend quiz

```
admin credentials
email: admin@test.com
pass:  1234
```

```
studen credentials
email: student@test.com
pass:  1234
```

7. Then compile all CSS & JS files together using this command

```
npm install 
npm install --save-dev vite@^5.0.0 @vitejs/plugin-vue@^5.0.0
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