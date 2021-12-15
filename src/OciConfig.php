<?php
declare(strict_types=1);

namespace Hitrov;

class OciConfig
{
    public string $region = 'ap-singapore-1';
    public string $ociUserId = 'ocid1.user.oc1..aaaaaaaagvjovj2n65q4ulyhh4gthokuuvu5pnltcuamjilims2fg2wdujoq';
    public string $tenancyId = 'ocid1.tenancy.oc1..aaaaaaaa3a4gpk4a5qfaeidw7qbr5rpjht2w57tz7qnh24wemrgdntoco6jq';
    public string $keyFingerPrint = '8e:08:e1:83:ce:75:ee:51:4a:8b:09:44:5b:08:2e:88';
    public string $privateKeyFilename = '';
    public string $availabilityDomain = 'Cplu:AP-SINGAPORE-1-AD-1';
    public string $subnetId = 'ocid1.subnet.oc1.ap-singapore-1.aaaaaaaaosyfpkxwbxaqwzem2d2qnj2twmdsjrxc2bhiz23km2eol4jcnvla';
    public string $imageId = 'ocid1.image.oc1.ap-singapore-1.aaaaaaaa3vsfhyvqje5yyn2vrr52tfnm5j2xovxyl2uojexvead5mesrh7ba';
    public ?int $ocpus;
    public ?int $memoryInGBs;

    public function __construct(
        string $region,
        string $ociUserId,
        string $tenancyId,
        string $keyFingerPrint,
        string $privateKeyFilename,
        string $availabilityDomain,
        string $subnetId,
        string $imageId,
        int $ocups = 16,
        int $memoryInGBs = 96
    )
    {
        $this->region = $region;
        $this->ociUserId = $ociUserId;
        $this->tenancyId = $tenancyId;
        $this->keyFingerPrint = $keyFingerPrint;
        $this->privateKeyFilename = $privateKeyFilename;
        $this->availabilityDomain = $availabilityDomain;
        $this->subnetId = $subnetId;
        $this->imageId = $imageId;
        $this->ocpus = $ocups;
        $this->memoryInGBs = $memoryInGBs;
        $this->imageId = $imageId;
    }
}
