run "sed -i 's/##EAAPPNAME##/\"#{config.app}\"/' #{config.current_path}/init.php"
run "mkdir #{config.shared_path}/uploads"
run "chmod -R 777 #{config.shared_path}/uploads"
