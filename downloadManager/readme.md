# Download Manager

This class manages creating and preparing small sized text files for download(used a lot in course of application).
It uses session to store download data for users to retrive when needed and also has a timeout for each file that expires after a defined number of seconds.
It also utilizes a garbage collector to delete expired files and ensures that there are not so much expired files left on disk.
It's better to garbage collect expired files in scheduled periods.