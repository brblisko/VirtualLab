#! /bin/bash

systemctl stop jupyter.service
umount /home/student
systemctl start jupyter.service