# kirby-jellyfin
![jf](https://github.com/GeneMarks/kirby-jellyfin/assets/68919132/da3a65c8-44c7-4762-bdc7-71ba59a432ae)

Kirby plugin that shows recently watched content from a Jellyfin server. See a [live preview](https://genemarks.net/about) by clicking the "Watching" tab.

## Features
- Generates JSON output of recently watched movie and tv items, their titles, the dates they were played, how many plays they have, etc.
- Saves .webps of each item's thumbnail.
- Caches data for a user-set time before allowing another api call to the Jellyfin server. The cache file is also used if the Jellyfin server is unreachable.
- Automatically deletes unused thumbnails and cleans up old log files.

## Prerequisites
- Jellyfin server
- PHP 8.1+ backend

## Setup
1. Clone this repo into your website's `site > plugins` folder.
2. Create a file named `jfserver.ini` or edit the example's filename.
3. Fill in the following information:
    1. `host` - The root of your site without the scheme
    2. `apikey` - Generated in the Jellyfin dash under the `Advanced` section.
    3. `userid` - The user whose watch history will be displayed. Found in the URL after going to the Jellyfin dash, selecting `Users`, and selecting the user. You have to use the same format as the example, i.e. put dashes in between groups of characters like so: 8-4-4-4-12.
    4. `imagesdir` - The directory that will store the thumbnail images relative to the root webfolder.
    5. `cachetime` - The time to store cached data before allowing it to be replaced, in seconds. The default is 1 hour.
    6. `itemlimit` - The max amount of recently watched items to fetch.
```ini
host=myjellyserver.com
apikey=12345678912345678912345678912345
userid=12345678-1234-1234-1234-123456789123
imagesdir=assets/images/jellyfin/
cachetime=3600
itemlimit=20
```
4. View the provided `jellyfin.js` file to see an example implementation. You can see a live example of this implementation [here](https://genemarks.net/about) by clicking the "Watching" tab.
