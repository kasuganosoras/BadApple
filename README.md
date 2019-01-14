# BadApple
Bad Apple in Terminal

用 PHP 写的 Bad Apple 命令行 / WebSocket 动画，原理是先提取出视频所有帧，再转换为字符画。

## 命令行版本观赏方式

1. 下载 badapple-1.2.zip 并解压。
2. 执行 `php badapple-1.2.php`（需要PHP环境）

推荐在 Linux 机器本机终端运行以获得最佳的观赏效果，建议将终端大小调整到 1650x1000 效果更佳。

## WebSocket 版本观赏方式

1. 下载 websocket.php、websocket.html、badapple.txt 以及 badapple.mp3
2. 浏览器打开 websocket.html
3. 在命令行执行 `php websocket.php`，开始观看

建议在本机观看，不然会有很大的延迟，体验不好

## 音画不同步问题

如果出现音画不同步，请手动调整 websocket.php 第 66 行的 `usleep(31916)`，将 `31916` 改为一个合理的数值。

建议每次调整数值在 300 以内，如果音乐快了，就减少 usleep 数值，反之则增加数值。

通过不断调整最终即可实现音画完全同步（然而只要你再打开个程序就会发现又不同步了）

CPU 占用率对于这玩意还是影响很大的
