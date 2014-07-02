These files all go in the root directory of the drupal installation,
except for settings.php, which goes in drupal_root/sites/default/,
and the badges images, which go in drupal_root/sites/default/files/
(just the images, not the whole folder named badges)

index.php will always redirect to ensembles.php making it impossible
to get to the Drupal login page itself by visiting the website. If an
admin needs to log in to Drupal for some reason, there is a note in
index.php to comment out a particular block, which will allow access
to the Drupal site by visiting the website.