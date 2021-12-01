<?php
declare(strict_types=1);


// useful when script is being executed by cron user
$pathPrefix = ''; // e.g. /usr/share/nginx/oci-arm-host-capacity/

require "{$pathPrefix}vendor/autoload.php";
use Hitrov\OciApi;
use Hitrov\OciConfig;

$config1 = new OciConfig(
    getenv('OCI_REGION') ?: 'ap-singapore-1', // region
    getenv('OCI_USER_ID') ?: 'ocid1.user.oc1..aaaaaaaagvjovj2n65q4ulyhh4gthokuuvu5pnltcuamjilims2fg2wdujoq', // user
    getenv('OCI_TENANCY_ID') ?: 'ocid1.tenancy.oc1..aaaaaaaa3a4gpk4a5qfaeidw7qbr5rpjht2w57tz7qnh24wemrgdntoco6jq', // tenancy
    getenv('OCI_KEY_FINGERPRINT') ?: '8e:08:e1:83:ce:75:ee:51:4a:8b:09:44:5b:08:2e:88', // fingerprint
    getenv('OCI_PRIVATE_KEY_FILENAME') ?: "ladcva-12-01-07-27.pem", // key_file
    getenv('OCI_AVAILABILITY_DOMAIN') ?: 'Cplu:AP-SINGAPORE-1-AD-1', // availabilityDomain
    getenv('OCI_SUBNET_ID') ?: 'ocid1.subnet.oc1.ap-singapore-1.aaaaaaaaosyfpkxwbxaqwzem2d2qnj2twmdsjrxc2bhiz23km2eol4jcnvla', // subnetId
    getenv('OCI_IMAGE_ID') ?: 'ocid1.image.oc1.ap-singapore-1.aaaaaaaa3vsfhyvqje5yyn2vrr52tfnm5j2xovxyl2uojexvead5mesrh7ba', // imageId
    (int) getenv('OCI_OCPUS') ?: 16,
    (int) getenv('OCI_MEMORY_IN_GBS') ?: 96
);

$configs = [
    $config1,
    // array of configs is used for the case when you have multiple accounts in different home regions
];

$api = new OciApi();

foreach ($configs as $config) {
    $shape = getenv('OCI_SHAPE') ?: 'VM.Standard.A1.Flex'; // or VM.Standard.E2.1.Micro

    $maxRunningInstancesOfThatShape = 1;
    if (getenv('OCI_MAX_INSTANCES') !== false) {
        $maxRunningInstancesOfThatShape = (int) getenv('OCI_MAX_INSTANCES');
    }

    [ $listResponse, $listError, $listInfo ] = $api->getInstances($config);

    if ($listError || (!empty($listInfo) && $listInfo['http_code'] !== 200)) {
        echo "$listError: $listResponse\n";
        continue;
    }

    $listR = json_decode($listResponse, true);
    if (json_last_error() || !is_array($listR)) {
        echo "Got JSON error while getting instances or non-array. Response: $listResponse. User: $config->ociUserId\n";
        continue;
    }

    $existingInstances = $api->checkExistingInstances($config, $listR, $shape, $maxRunningInstancesOfThatShape);
    if ($existingInstances) {
        echo "$existingInstances\n";
        continue;
    }

    $sshKey = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQCgK3gH5L1msTr8F1S4vffPV178d7ZU9UOWjhVjdBAODATA1OA/Emo0mgLQPR0VpzA2FRgGJEyUWrkY/+dh5wzYsQ2oYoVrPXN6dxuKTCuv4iznTcRqUU9Xu0RA1gUUqX3hXeCsaQ58ZVSlvxW1Bc+0WFmpdnaBcZlrN58aK41G1sMvYiY5W1aX+Xa07kBuvZlJyv53iyOV89K3DlFp5b6xbar5IMnEPbMVgdRGriRx5roaJ+hUjFGp1cmwrd81Q2GrTz8xalJ8qk8rPnfDvkaysUGeSsu5Gps32QIBCD1YEK4PLMXYTwbHcHWfonkeIemftuAf8EnJOjVcwy5l4cYCARVYYFX1DI1Thir3n3cEa9T2W0XnlDgoD1oDfXQjceC2CBJ7N3cJFXj9diLQUqJW80kOwu4E9Uvdie6gnrOFDJJQDHsjiBx+I9dBM7HiPt5DvvPGNxXOzXi1VMfD8gn1mejMqwhpht0K7sX9b8cU1QGHG04HmWswd0ROxivFXE8= mac@macs-MacBook-Pro.local'; // ~/.ssh/id_rsa.pub contents
    [ $response, $error, $info ] = $api->createInstance($config, $shape, $sshKey);

    $r = json_decode($response, true);
    $prettifiedResponse = json_encode($r, JSON_PRETTY_PRINT);
    echo $prettifiedResponse;
}
