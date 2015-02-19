---
layout:     post
title:      Updating the Firmware on the LSI 9201-16i
excerpt:    A step by step guide on how to update the firmware and BIOs on the LSI 9201-16i SAS controller.
date:       2015-02-19 12:35:12
categories: 
tags:       SAS, RAID, LSI, Firmware, BIOs
image:      /assets/articles/images/2015-02-19-updating-LSI-9201-16i-firmware/IMG_9941_darkened-2-2560.jpg
---
{% include attributes.md %}

After recently upgrading my FreeNAS system to from 8.3 to 9.3, I began receiving alerts about the LSI driver for my SAS controller, the LSI 9201-16i, being out of date:

```
WARNING: Firmware version 5 does not match driver version 16 for /dev/mps0.
```

While attempting to update the card with the UEFI shell, I found the process to be somewhat convoluted.  The following guide details step-by-step how to upgrade the firmware and BIOs on the LSI 9201-16i SAS card using a UEFI shell.

----------

### Downloading the new Firmware

You can grab your desired firmware version from the LSI download page.  Only the two most recent versions are available from the product's page, so I found myself having to go through the [full product download page][LSI Firmware Page].  The previous versions can be found under the "Archive" link.

![]({{ "firmware-archive-link.png" | prepend: asset_image }})

You'll want to grab two files from the archive: `Installer_PXX_for_UEFI`, and `9201_16i_package_PXX_IT_Firmware_BIOS_for_MSDOS_Windows`.  I was specifically trying to upgrade to P16 for FreeNAS 9.3, for which the UEFI and DOS packages can be found [here][LSI EFI] and [here][LSI DOS], respectively.

While these are downloading, grab a thumb drive.  The drive doesn't require any special formatting so long as it has some variation of a FAT filesystem.  Create a folder on the thumbdrive, and copy the following files from each ZIP:

**From the EFI ZIP:**

 * `sas2flash_efi_ebc_rel/sas2flash.efi`

**From the DOS ZIP:**

 * `Firmware/HBA_9201_16i_IT/9201-16i_it.bin`
 * `sasbios_rel/mptsas2.rom`

### Updating the Firmware & BIOs

With the thumbdrive prepared, plug it in to your system and reboot into the UEFI shell.  Once in the shell, `mount` your thumbdrive (typically, the drive is either `fs0` or `fs1`, making the command `mount fs0`).  To confirm you have the correct drive, you can use most of your typical *NIX navigation commands: `ls` to list the contents of the directory, and `cd` to navigate into your subfolder (if you created one).

With your thumb drive selected, list out the RAID cards in the system with the `sas2flash.efi -listall` command.

![]({{ "IMG_9946-crop-2560.jpg" | prepend: asset_image }})

If you see your card, it's time to run the update command: `sas2flash.efi -o -f 9201-16i_it.bin -b MPTSAS2.ROM`.  

![]({{ "IMG_9947-crop-2560.jpg" | prepend: asset_image }})

One the command has completed, list the cards again to make sure it has run successfully.

![]({{ "IMG_9959-crop-2560.jpg" | prepend: asset_image }})

If you see the expected firmware version, you're all set.  Issue a `reset` command to restart the system.

I hope this helps shed some light on this process!




[LSI Firmware Page]: http://www.lsi.com/support/pages/download-results.aspx?component=Storage+Component&productfamily=Host+Bus+Adapters&productcode=P00027&assettype=0&productname=LSI+SAS+9201-16i
[LSI EFI]: http://www.lsi.com/downloads/Public/Host%20Bus%20Adapters/Host%20Bus%20Adapters%20Common%20Files/SAS_SATA_6G_P16/Installer_P16_for_UEFI.zip
[LSI DOS]: http://www.lsi.com/downloads/Public/Host%20Bus%20Adapters/Host%20Bus%20Adapters%20Common%20Files/SAS_SATA_6G_P16/9201_16i_Package_P16_IT_Firmware_BIOS_for_MSDOS_Windows.zip
