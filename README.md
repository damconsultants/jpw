# JPW
Magento 2 JPW Extension gives you a great opportunity to upload more “images”, “videos” and “documentation” to the JPW Media Library next to the Magento admin.

1) How to Install extension using Manual Installation?
  1.1. Download the JPW extension
  1.2. Unzip the file in a temporary directory/folder with name as JPW.
  1.3. Put Admin IP Restriction directory as per this folder structure: project_root/app/code/ DamConsultants / JPW
  1.4. Run the following command in Magento 2 root folder
    1.4.1. php bin/magento setup:upgrade
    1.4.2. php bin/magento setup:di:compile
    1.4.3. php bin/magento setup:static-content:deploy
    
2) Using Composer
      composer require damconsultants/jpw:1.0.4
