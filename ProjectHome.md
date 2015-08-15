## ircPlanet Services for ircu ##
### About ###
The ircPlanet services are a suite of P10-based channel, operator, and nickname services written in PHP for ircu2.10.08 and above. They are extremely object-oriented and run from the command line.

Additionally, a central core of common service code is at the base of each service, making it easy for service developers versed in PHP to write their own ircu services quickly and easily.

### Service Details ###
Several core services are included in the ircPlanet services:
  * **Nickname Service** - Provides nickname registration and account management features to end users.
  * **Channel Service** - Provides channel registration and management features to end users in the traditional Undernet style.
  * **Operator Service** - Provides advanced network management capabilities to operators and admins.
  * **Defense Service** - Checks each user's IP against a list of public DNS blacklists that track malicious, spammy, and drone activity across the internet (does not perform active scans against user endpoints).
  * **Stats Service** - Currently in very much of a beta state, keeps a partial network state and accompanying historical statistics in MySQL for use on web sites, reporting, etc.

### Reporting Bugs and Requesting Features ###
Found a bug? Have we missed a feature that you think deserves to be included? Let us know! Use these links to do so:
  * [Report a bug](http://code.google.com/p/ircplanet/issues/entry?template=Defect%20report%20from%20user)
  * [Request a feature](http://code.google.com/p/ircplanet/issues/entry?template=Feature%20request)
  * [Browse all issues](http://code.google.com/p/ircplanet/issues/list)

### Source ###
You can check out a copy of our source code, or you can view the entire repository in your browser at http://github.com/briancline/ircplanet/tree/master. To check out a copy of the ircPlanet services source, issue the following command from a command prompt:
```
svn checkout http://svn.github.com/briancline/ircplanet
```

To update a copy of the source you have already checked out, simply go to the root `ircplanet` directory and issue the following command:
```
svn update
```


### Recent Changes ###
You may find a running list of all changes at http://code.google.com/p/ircplanet/source/list.



---


&lt;wiki:gadget url="http://www.ohloh.net/projects/5111/widgets/project\_basic\_stats.xml" height="220"  border="1" /&gt;
&lt;wiki:gadget url="http://www.ohloh.net/projects/5111/widgets/project\_thin\_badge.xml" height="36"  border="0" /&gt;
&lt;wiki:gadget url="http://www.ohloh.net/projects/5111/widgets/project\_users.xml" height="100"  border="0" /&gt;