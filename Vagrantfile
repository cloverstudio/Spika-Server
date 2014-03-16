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
    sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php5 php5-curl phpunit curl git-core php5-xdebug postfix mysql-server php5-mysql php5-gd

    #http://www.giocc.com/installing-phpunit-on-ubuntu-11-04-natty-narwhal.html
    sudo pear upgrade pear
	sudo pear channel-discover pear.phpunit.de
	sudo pear channel-discover components.ez.no
	sudo pear channel-discover pear.symfony.com
	sudo pear install --alldeps phpunit/PHPUnit

    a2enmod rewrite
    sed -i '/AllowOverride None/c AllowOverride All' /etc/apache2/sites-available/default    

    sudo rm -rf /var/www
    sudo ln -s /vagrant_data /var/www

    sudo /etc/init.d/apache2 restart
    sudo mkdir -p /vagrant_data/logs
    sudo mkdir -p /vagrant_data/uploads
    sudo chmod 777 /vagrant_data/logs
    sudo chmod 777 /vagrant_data/uploads
    sudo php /vagrant_data/composer.phar install -d /vagrant_data/

    echo 'please open http://localhost:8080/wwwroot/installer to finish setup'
    
  EOS
end
