Creates Issues on github or bitbucket based on the repository url.

The script accept the the following parameters:
·         Username
·         Password
·         Repository url
·         For github, something like https://github.com/:username/:repository
·         For bitbucket, something like https://bitbucket.org/:username/:repository
·         Issue title
·         Issue description


For demo purpose:
https://bitbucket.org/shaangola/demorep

https://github.com/shaangola/demorep

these are the demo repository for this code.

For Installation.

Step 1:

Unzip the folder wherever you want.

Step 2:

for above step i suppose that you place these files in c:/test where <test> is folder name in c: drive.

then open command prompt and go to this location where you placed your files c:/test 

Step 3: write php in command prompt if it shows error then your php path is not correctly configured to configure this please follow the step given 
	
	1. From the desktop, right-click My Computer and click Properties.
	2. In the System Properties window, click on the Advanced tab.
	3. In the Advanced section, click the Environment Variables button.
	4. Finally, in the Environment Variables window the Path variable in the Systems Variable section and click the 	   Edit button. Add the path where your php installtion folder is for example if there is wamp then C:\wamp\bin\php\php<version>.

if everything goes good then 

write this command in command prompt

php createissue.php do <username> <password> <repo> <title> <content>

<username> - your username.
<password> - your password.
<repo>     - Url of the repository.
<title>    - Title of the issue.
<content>  - Description of the issue..

for example which is working one

php createissue.php do shaangola demo123 https://github.com/shaangola/demorep/ "Test Success" "Congratulation you are hired !!".

Then go to this url

https://github.com/shaangola/demorep/issues?state=open

same for the bitbucket just give the bitbucket repository url and the credentials on bitbucket.

https://github.com/shaangola/demorep.git
