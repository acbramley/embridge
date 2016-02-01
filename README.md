### Summary

The EMBridge module (located here),extends the image management functionalities
of Drupal by connecting it to EnterMedia, an open-source digital asset management
system distributed under the GNU General Public License, used to search, manage, 
reuse, and track all digital files.

### EMBridge Configuration

* Go to Administer > Configuration > Web Services > EMBridge Settings

  - enter host name and port number for EnterMedia DAM server.
  - enter login and password for EnterMedia server.
  - Save the configuration
  - click "Test Connection" to verify if we can connect and login to EnterMedia  server. If we are getting "success" message, then our basic server configuration for EnterMedia are complete.
  
* Continue settings for each catalog (audio, interactive, photo and video).
  - Renditions: selected renditions will be included in embridge_field
  for nodes.
  - Search Fields: selected fields will be displayed in EnterMedia Search form
  as search conditions.
  - Search Result Fields: selected fields will be included in EnterMedia Search
  result.

### EMBridge Field Configuration

COMING SOON.