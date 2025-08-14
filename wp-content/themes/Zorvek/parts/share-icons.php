<?php
$link = get_page_link();
?>

<ul>
    <p>Share</p>
    <li>
        <a target="_blank" href="http://www.facebook.com/sharer.php?u=<?php echo $link ?>"><i class="fa-brands fa-facebook"></i>Facebook</a>
    </li>
    <li>
        <a target="_blank" href="http://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link ?>"><i class="fa-brands fa-linkedin"></i>LinkedIn</a>
    </li>
    <li>
        <a target="_blank" href="https://twitter.com/share?url==<?php echo $link ?>"><i class="fa-brands fa-square-x-twitter"></i>Twitter</a>
    </li>
    <li class='relative'>
        <a href='#' class="copy-link" data-link="<?php echo $link ?>">
            <i class="fa-solid fa-link"></i> Link
        </a>
        <span class="tooltip">Link copied</span>
    </li>
</ul>