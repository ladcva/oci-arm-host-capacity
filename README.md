# Resolving Oracle Cloud "Out of Capacity" issue

<p align="center">
  <a href="https://github.com/hitrov/oci-arm-host-capacity/actions"><img src="https://github.com/hitrov/oci-arm-host-capacity/workflows/Tests/badge.svg" alt="Test"></a>
  <a href="https://discord.gg/fKZQQStjMN"><img src="https://img.shields.io/discord/893301913662148658?label=Discord&logo=discord&logoColor=white" alt="Discord"></a>
</p>

## Generating API key

After logging in to [OCI Console](http://cloud.oracle.com/), click profile icon and then "User Settings"

![User Settings](images/user-settings.png)

Go to Resources -> API keys, click "Add API Key" button

![Add API Key](images/add-api-key.png)

Make sure "Generate API Key Pair" radio button is selected, click "Download Private Key" and then "Add".

![Download Private Key](images/download-private-key.png)

Copy the contents from textarea and save it to file with a name "config". I put it together with *.pem file in newly created directory /home/ubuntu/.oci

![Configuration File Preview](images/config-file-preview.png)

## Installation

Clone this repository
```bash
git clone https://github.com/hitrov/oci-arm-host-capacity.git
```
run
```bash
cd oci-arm-host-capacity/
composer install
```

## Adjust script file

You need to slightly adjust index.php file - by changing OciConfig constructor arguments.

### Adjust OciConfig arguments 1–5

Arguments 1–5 (region, user, tenancy, fingerprint, path to private key) should be taken from textarea during API key generation step.

### Adjust OciConfig arguments 6-8

In order to acquire availabilityDomain, subnetId, imageId you must start instance creation process from the OCI Console in the browser (Menu -> Compute -> Instances -> Create Instance)

Change image and shape and make sure that "Always Free Eligible" availabilityDomain label is there:

![Changing image and shape](images/create-compute-instance.png)

Adjust Networking section, set "Do not assign a public IPv4 address" checkbox. If you don't have existing VNIC/subnet, please create VM.Standard.E2.1.Micro instance before doing everything.

![Networking](images/networking.png)

"Add SSH keys" section does not matter for us right now. Before clicking "Create"…

![Add SSH Keys](images/add-ssh-keys.png)

…open browser's dev tools -> network tab. Click "Create" and wait a bit - most probably you'll get "Out of capacity" error. Now find /instances API call (red one)…

![Dev Tools](images/dev-tools.png)

…and right click on it -> copy as curl. Paste the clipboard contents in any text editor and review the - data-binary parameter. Find availabilityDomain, subnetId, imageId. Use them as 6,7 and 8 arguments, respectively, to the OciConfig constructor.

OciConfig also has the last two arguments - ocpus and memoryInGBs (respectively). They are optional and are equals 4 and 24 by default. Of course, you can adjust them.  Possible values are 1/6, 2/12, 3/18 and 2/24, respectively. Please notice that "Oracle Linux Cloud Developer" image can be created with at least 8GB of RAM.

### Set public key value

In order to have secure shell (SSH) access to the instance you need to have a keypair, e.g. ~/.ssh/id_rsa and ~/.ssh/id_rsa.pub. Second one (public key) filename should be provided to a command below. The are plenty of tutorials on how to do that, we won't cover this part here.

Change the string variable $sshKey - paste the contents of your public key ~/.ssh/id_rsa.pub (or you won't be able to login into the newly created instance).

## Running the script

```bash
php /path/to/oci-arm-host-capacity/index.php
```

I bet that the output (error) will be similar to the one in a browser a few minutes ago

```json
{
    "code": "LimitExceeded",
    "message": "The following service limits were exceeded: standard-a1-memory-count, standard-a1-core-count. Request a service limit increase from the service limits page in the console. "
}
```

You can now setup periodic job to run the command

```bash
EDITOR=nano crontab -e
```

Add new line to execute the script every minute and log the output

```bash
* * * * * /usr/bin/php /path/to/oci-arm-host-capacity/index.php > /path/to/script.log
```

..and save the file.

There could be cases when cron user won't have some permissions, the easiest way to solve it is to put the code into web server's accessible directory e.g. /usr/share/nginx/html and setup cron this way:

```bash
* * * * * curl http://server.add.re.ss/oci-arm-host-capacity/index.php
```

You can also visit the URL above and see the same command output as by running from the shell.

Before the instance creation, script will call [ListInstances](https://docs.oracle.com/en-us/iaas/api/#/en/iaas/20160918/Instance/ListInstances) OCI API method and check whether there're already existing instances with the same `$shape`, as well as number of them `$maxRunningInstancesOfThatShape`(you can safely adjust the last one if you wanna e.g. two VM.Standard.A1.Flex with 2/12 each).

Script won't create new instance if current (actual) number return from the API exceeds the one from `$maxRunningInstancesOfThatShape` variable.

In case of success the JSON output will be similar to

![Launch success 1](images/launch-output-1.png)

![Launch success 2](images/launch-output-2.png)

## Assigning public IP address

We are not doing this during the command run due to the default limitation (2 ephemeral addresses per compartment). That's how you can achieve this. When you'll succeed with creating an instance, open OCI Console, go to Instance Details -> Resources -> Attached VNICs by selecting it's name

![Attached VNICs](images/attached-vnics.png)

Then Resources -> IPv4 Addresses -> … -> Edit

![IPv4 Addresses](images/ipv4-addresses.png)

Choose ephemeral and click "Update"

![Edit IP Address](images/edit-ip-address.png)

## Conclusion

That's how you will login when instance will be created (notice opc default username)

```bash
ssh -i ~/.ssh/id_rsa opc@ip.add.re.ss
```

If you didn't assign public IP, you can still copy internal FQDN or private IP (10.x.x.x) from the instance details page and connect from your other instance in the same VNIC. e.g.

```bash
ssh -i ~/.ssh/id_rsa opc@instance-20210714-xxxx.subnet.vcn.oraclevcn.com
```
