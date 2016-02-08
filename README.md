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
  
* Continue settings for each application.
  - Once a connection is established, add an "Application" on the Applications tab.
  - Applications are related to when adding a Field.
  - Conversions: Conversions can be used in field formatteds
  - Search Fields: selected fields will be displayed in EnterMedia Search form
  as search conditions.

### EMBridge Field Configuration

Add a field to your content type and select _*Embridge Asset Item*_

Configure the Allowed Extensions, and Maximum upload size. These will be used to validate the files before uploading to the server.

The File Directory setting is a temporary storage location for files before being uploaded to the EMDB instance.

The Application chosen will dictate which fields can be searched on, and which conversions can be used in the field
formatter.

