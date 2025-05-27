$Action = New-ScheduledTaskAction -Execute "C:\MAMP\htdocs\kiosko-scrapping\update_task.bat"
$Trigger = New-ScheduledTaskTrigger -Daily -At "7:00AM"
$Settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -DontStopOnIdleEnd -AllowStartIfOnBatteries
$Task = Register-ScheduledTask -TaskName "KioskoScraping" -Action $Action -Trigger $Trigger -Settings $Settings -Description "Actualiza el scraping de portadas todos los días a las 7:00 AM hora de Uruguay" 