#!/bin/bash
HOMEDIR=/home/ec2-user
GITDIR=$HOMEDIR/git/lego
WWWDIR=$HOMEDIR/htdoc

cd $GITDIR;
git pull;

rsync -r --delete --exclude-from=$GITDIR/sysadm/rsync_htdoc_no_del $GITDIR/htdoc/ $WWWDIR;
