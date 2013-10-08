# -*- mode: ruby -*-
# vi: set ft=ruby :

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "precise64"
  config.vm.box_url = "http://files.vagrantup.com/precise64.box"

  config.vm.network :forwarded_port, guest: 80, host: 8080
  config.vm.synced_folder "./", "/vagrant_data", :owner=> 'vagrant', :group=>'www-data', :mount_options => ['dmode=775','fmode=775']

  config.vm.provision :shell, :inline => <<-EOS
    sudo apt-get update
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php5 php5-curl php5-mysql mysql-server couchdb curl
    sudo rm -rf /var/www
    sudo ln -s /vagrant_data /var/www
    sudo mysql -u root -e "create database spika"
    curl -X PUT http://127.0.0.1:5984/spikademo
    sudo /etc/init.d/apache2 restart
    sudo mkdir -p /vagrant_data/HookUpServer/hookup_push/tmp/log
  EOS
end
