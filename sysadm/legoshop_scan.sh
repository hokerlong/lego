#!/bin/bash
HOMEDIR=/home/ec2-user
GITDIR=$HOMEDIR/git/lego
SCANDIR=$GITDIR/lego_scan;

/usr/bin/php $GITDIR/htdoc/legoshop_scan.php > $SCANDIR/snapshot.csv;

TIME=$(date +%Y%m%d%H%M);

cd $SCANDIR
git add snapshot.csv;
git commit -m "update $TIME snapshot";
git push;

mv snapshot.csv snapshot_$TIME.csv;
