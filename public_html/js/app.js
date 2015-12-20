var App = {
    elementInViewport : function(el) {
        var top = el.offsetTop;
        var left = el.offsetLeft;
        var width = el.offsetWidth;
        var height = el.offsetHeight;

        while(el.offsetParent) {
          el = el.offsetParent;
          top += el.offsetTop;
          left += el.offsetLeft;
        }

        return (
          top >= window.pageYOffset &&
          left >= window.pageXOffset &&
          (top + height) <= (window.pageYOffset + window.innerHeight) &&
          (left + width) <= (window.pageXOffset + window.innerWidth)
        );
    },
    user : {
        csrfToken : null,
        id : null,
        isLogged : function() {
            return null !== App.user.id;
        },
        logout : function() {
            var form = document.createElement("form");
            form.setAttribute("action", "/logout");
            form.setAttribute("method", "post");
            var inp = document.createElement("input");
            inp.setAttribute("type", "hidden");
            inp.setAttribute("name", "_csrf");
            inp.setAttribute("value", App.user.csrfToken);
            form.appendChild(inp);

            document.body.appendChild(form);
            form.submit();
        }
    },
    update : {
        category : null,
        Loader : function(data) {
            if (!data.updatesCont) {
                return null;
            }

            if (false !== data.loadOnScroll) {
                $(window).scroll(function () {
                    if ($(window).scrollTop() + $(window).height() >=
                        $(document).height() - $(document).height() / 15
                    ) {
                        obj.load();
                    }
                });
            }

            var loaded = false;
            var loadedBefore = false;
            var page = 0;

            var loading = false;

            var alreadyLoaded = {};

            var msnry = new Masonry(data.updatesCont , {
                columnWidth: 500,
                isFitWidth: true,
                isAnimated: false,
                transitionDuration: 0,
                gutter : 10
            });

            function setLoading() {
                if (data.loadingEle) {
                    data.loadingEle.setAttribute('class', '');
                    loading = true;
                }
            }

            function setLoaded() {
                if (data.loadingEle) {
                    data.loadingEle.setAttribute('class', 'hidden');
                }
            }

            var obj = {
                load : function() {
                    data.ajaxData = data.ajaxData || {};

                    if (loading) {
                        return;
                    }

                    setLoading();

                    data.ajaxData.page = page;

                    $.ajax({
                        type : data.method || 'get',
                        url  : data.url,
                        data : data.ajaxData,
                        success : function(response) {
                            for (var i = 0; i < response.length; i++) {
                                if (alreadyLoaded[response[i].id]) {
                                    alreadyLoaded[response[i].id] = true;
                                    continue;
                                }

                                var newEle = App.update.renderUpdate(response[i], msnry);
                                data.updatesCont.appendChild(newEle.updateCont);
                                msnry.appended(newEle.updateCont);

                                if (newEle.img) {
                                    newEle.img.onload = function() {
                                        msnry.layout();
                                    };
                                } else {
                                    msnry.layout();
                                }
                            }

                            if (0 < response.length) {
                                loading = false;
                            } else if (!loadedBefore && data.noResultEle) {
                                data.noResultEle.setAttribute('class', '');
                            }

                            page++;
                            loadedBefore = true;
                        }
                    }).always(function() {
                        setLoaded();
                    });
                }
            };

            return obj;
        },
        renderUpdate : function(data, msnry) {
            var updateCont = document.createElement('div');
            updateCont.setAttribute('class', 'update-cont');
            var updateInfoCont = document.createElement('div');
            var updateLink1 = document.createElement('a');
            var updateLinkCont = document.createElement('h3');
            updateLinkCont.setAttribute('class', 'update-title-list');
            updateLinkCont.setAttribute('style', 'display : inline-block;');
            
            var updateUrl = (App.update.category ? ('/' + App.update.category) : '') + data.updateUrl;
            
            updateLink1.setAttribute('href', updateUrl);
            updateLink1.setAttribute('class', 'update-link');

            var updateLink2 = document.createElement('a');
            updateLink2.setAttribute('href', updateUrl);

            updateLink1.innerHTML = data.description;
            updateInfoCont.setAttribute('style', 'font-weight : bold;');

            updateLinkCont.appendChild(updateLink1);
            updateInfoCont.appendChild(updateLinkCont);

            if (data.activity_type) {
                var activityType = document.createElement('span');

                var c = 0;

                if (data.activity_type & 4) {
                    c++;
                    activityType.innerHTML = 'posted';
                }
                if (data.activity_type & 1) {
                    activityType.innerHTML += (0 < c ? ', ' : '') + 'upvoted';
                    c++;
                }
                if (data.activity_type & 2) {
                    activityType.innerHTML += (0 < c ? ', ' : '') + 'commented';
                }

                updateInfoCont.appendChild(activityType);
                activityType.setAttribute('style', 'margin-left: 5px; color: gray; font-weight: initial;');
            }

            var imageCont = document.createElement('div');
            imageCont.setAttribute('style', 'border: 1px solid #ddd;');
            var _img = null;

            if (data.is_gif == 0) {
                var image = document.createElement('img');
                image.setAttribute('src', data.imgUrl);
                image.setAttribute('class', 'image');
                image.setAttribute('style', 'width : 100%;');
                _img = image;

                updateLink2.appendChild(image);
                updateLink2.setAttribute('class', 'link-no-decor');
                imageCont.appendChild(updateLink2);

                if (data.isHighImage) {
                    var d1 = document.createElement('div');
                    d1.setAttribute('style', 'position: relative;');

                    var d1 = document.createElement('div');
                    d1.setAttribute('style', 'position: relative;');

                    var d2 = document.createElement('div');
                    d2.setAttribute('style', 'color: #669CAD; text-decoration: none; padding: 10px;');
                    var icon1 = document.createElement('i');
                    icon1.setAttribute('class', 'fa fa-share-square-o');
                    var text1 = document.createElement('span');
                    text1.setAttribute('class', 'view-fp');
                    text1.innerHTML = 'view full post';

                    d2.appendChild(icon1);
                    d2.appendChild(text1);

                    var s1 = document.createElement('span');
                    s1.setAttribute('style' , 'width: 100%; height: 15px; display: block; position: absolute; top: -15px;');

                    d1.appendChild(s1);
                    d1.appendChild(d2);

                    updateLink2.appendChild(d1);
                }
            } else {
                function playVideo() {
                    video.play();
                    videoPlay.setAttribute('style', 'display: none;');
                }

                function pauseVideo() {
                    video.pause();
                    videoPlay.setAttribute('style', 'display: inline-block;');
                }

                var videoCont = document.createElement('div');
                videoCont.setAttribute('style', 'position: relative;');

                var videoPlay = document.createElement('span');
                videoPlay.setAttribute('class', 'play-video');

                var video = document.createElement('video');

                var paused = null;

                video.onclick = function() {
                    if (video.paused) {
                        playVideo();
                        paused = false;
                    } else {
                        pauseVideo();
                        paused = true;
                    }
                };

                videoCont.appendChild(video);
                videoCont.appendChild(videoPlay);

                video.setAttribute('style', 'vertical-align : top; min-height:209.78260869565px; width: 500px; max-width: 100%;');
                video.setAttribute('poster', '/images/' + data.id + '_poster.jpeg');
                video.setAttribute('muted', 'true');
                video.setAttribute('loop', 'true');
                video.setAttribute('width', '500');

                $(window).scroll(function () {
                    if (App.elementInViewport(video)) {
                        if (true !== paused) {
                            playVideo();
                        }
                    } else {
                        pauseVideo();
                        paused = null;
                    }
                });

                var video1 = document.createElement('source');
                video1.setAttribute('src', '/images/' + data.id + '_medium.mp4');

                var video2 = document.createElement('source');
                video2.setAttribute('src', '/images/' + data.id + '_medium.webm');

                video.appendChild(video1);
                video.appendChild(video2);

                imageCont.appendChild(videoCont);
            }

            updateCont.appendChild(updateInfoCont);
            updateCont.appendChild(imageCont);

            var buttonsCont = document.createElement('div');
            updateCont.appendChild(buttonsCont);

            if (data.tags) {
                var tagsCont = document.createElement('div');
                tagsCont.setAttribute('style', 'padding: 10px 5px;');
                updateCont.appendChild(tagsCont);

                for (var j in data.tags) {
                    var tag = document.createElement('a');
                    tag.setAttribute('href', '/search?term=' + data.tags[j].name);
                    tag.setAttribute('class', 'link');
                    tag.innerHTML = '#' + data.tags[j].name;
                    tagsCont.appendChild(tag);
                }
            }

            var sep = document.createElement('span');
            sep.innerHTML = ' - ';

            var comment = document.createElement('a');
            comment.innerHTML = 'comments ' + data.comments;
            comment.setAttribute('href', '/update/'+data.id+'#comments');
            comment.setAttribute('class', 'btn');

            var points = document.createElement('a');
            points.innerHTML = 'points ' + data.comments;
            points.setAttribute('href', '/update/' + data.id);
            points.setAttribute('class', 'btn');

            var commentCont = document.createElement('span');
            commentCont.appendChild(comment);

            buttonsCont.appendChild(points);
            buttonsCont.appendChild(sep);
            buttonsCont.appendChild(commentCont);

            if (App.user.isLogged()) {
                var upvote = document.createElement('i');

                App.button.vote1({
                    ele : upvote,
                    beforeVote : function(_data) {
                        _data.url = data.voted ? '/update/unvote' : '/update/upvote';
                        _data.data = {
                            id : data.id
                        };
                    },
                    onVote : function(response) {
                        if (response) {
                            data.voted = !data.voted;

                            upvote.setAttribute('style', data.voted ? 'margin-right: 7px; color: #09f;' : 'margin-right: 7px;');
                            upvote.setAttribute('title', data.voted ? 'unvote' : 'upvote');
                        }
                    }
                });

                var operButtonsCont = document.createElement('div');
                updateCont.appendChild(operButtonsCont);

                upvote.setAttribute('class', 'fa fa-thumbs-up update-btn');
                upvote.setAttribute('title', data.voted ? 'unvote' : 'upvote');
                upvote.setAttribute('style', data.voted ? 'margin-right: 7px; color: #09f;' : 'margin-right: 7px;');

                var commentBtn = document.createElement('a');
                commentBtn.setAttribute('class', 'fa fa-comment update-btn');
                commentBtn.setAttribute('href', '/update/' + data.id + '#create-comment');
                commentBtn.setAttribute('title', 'comment');

                operButtonsCont.appendChild(upvote);
                operButtonsCont.appendChild(commentBtn);
            }

            return {
                updateCont : updateCont,
                img : _img
            };
        }
    },
    comment : {
        createComment : function(data, addTo, callback, beforeAdd) {

            $.ajax({
                type : 'POST',
                url : "/comment/create",
                data : data,
                success : function(response) {
                    var comment = App.comment.renderComment(response);

                    if (addTo && comment) {
                        if (callback) {
                            callback();
                        }

                        if (undefined === beforeAdd || beforeAdd()) {
                            addTo.appendChild(comment);
                        }
                    }
                }
            });
        },
        load : function(data, addTo, loadReplies, callback) {
            $.ajax({
                type:'get',
                url : true === loadReplies ? "/comment/loadReplies" : "/comment/load",
                data : data,
                success : function(response) {
                    for (var i = 0; i < response.items.length; i++) {
                        var comment = App.comment.renderComment(response.items[i]);

                        if (comment) {
                            addTo.appendChild(comment);
                        }

                    }

                    if (callback) {
                        callback(
                            0 < response.items.length ? response.items[response.items.length - 1].posted_on : null,
                            response.items.length,
                            response.hasMore
                        );
                    }
                }
            });
        },
        renderComment : function(data) {
            if (!data.owner) {
                return null;
            }

            var isTopLevel = data.replies;

            var updateCont = document.createElement('div');
            updateCont.setAttribute('style', 'padding : 5px; border-bottom : 0px solid #ddd; margin-bottom: 10px; width: ' + (isTopLevel ? 700 : 600) + 'px; display: inline-block;');
            var updateInfoCont = document.createElement('div');
            var ownerLink = document.createElement('a');
            ownerLink.innerHTML = data.owner.username;
            ownerLink.setAttribute('href', data.owner.profileUrl);
            ownerLink.setAttribute('class', 'user-link');

            var postedAgo = document.createElement('span');
            postedAgo.innerHTML = data.postedAgo;
            postedAgo.setAttribute('style', 'margin-left : 5px; color : gray;');

            var img = document.createElement('img');
            img.setAttribute('src', data.owner.pictureUrl);
            img.setAttribute('width', '50');
            img.setAttribute('height', '50');
            img.setAttribute('style', 'float : left; border-radius : 5px;');

            updateInfoCont.setAttribute('class', 'comment-text');
            updateInfoCont.innerHTML = data.content;

            updateCont.appendChild(ownerLink);
            updateCont.appendChild(postedAgo);
            updateCont.appendChild(updateInfoCont);

            var buttonsCont = document.createElement('div');
            updateCont.appendChild(buttonsCont);

            buttonsCont.appendChild(App.button.vote({
                id : data.id,
                upvotes : data.upvotes,
                voted : data.voted,
                type : App.button.VOTE_TYPE_COMMENT
            }));

            if (data.replies) {
                var oldestReply = null;

                var repliesCont = document.createElement('div');
                var repliesList = document.createElement('div');
                repliesCont.setAttribute('style', 'border: 0px solid black; margin-left: 0px; margin-top: 15px;');

                if (App.user.isLogged()) {
                    var reply = document.createElement('span');
                    reply.setAttribute('class', 'btn');
                    reply.innerHTML = 'reply';

                    reply.onclick = function() {
                        replyInp.style.display = 'inline-block';
                        replyInp.focus();
                    };

                    var sep = document.createElement('span');
                    sep.innerHTML = ' - ';
                    buttonsCont.appendChild(sep);
                    buttonsCont.appendChild(reply);

                    var replyInp = document.createElement('textarea');
                    replyInp.setAttribute('style', "display : none;");
                    replyInp.setAttribute('placeholder', 'Write a reply...');

                    replyInp.onkeydown = function(e) {
                        if (13 === e.keyCode) {
                            e.preventDefault();
                            e.stopPropagation();

                            App.comment.createComment({
                                updateId : data.update_id,
                                replyTo : data.id,
                                content : replyInp.value
                            }, repliesList, function () {
                                replyInp.value = '';
                            }, function() {
                                return !data.hasMore;
                            });
                        }
                    };
                    repliesCont.appendChild(replyInp);
                }

                updateCont.appendChild(repliesCont);

                repliesCont.appendChild(repliesList);

                for (var i = 0; i < data.replies.length; i++) {
                    var reply = App.comment.renderComment(data.replies[i]);

                    if (reply) {
                        repliesList.appendChild(reply);
                    }
                }

                if (0 < data.replies.length) {
                    oldestReply = data.replies[data.replies.length - 1].posted_on;
                }

                if (data.hasMore) {
                    var loadMore = document.createElement('span');
                    loadMore.setAttribute('class', 'load-more-comments');
                    loadMore.innerHTML = 'Show more replies...';
                    loadMore.onclick = function() {
                        App.comment.load({
                            updateId : data.updateId,
                            replyTo : data.id,
                            last : oldestReply
                        }, repliesList, true, function(time, count, hasMore) {
                            oldestReply = time;

                            if (!hasMore) {
                                loadMore.setAttribute('class', 'load-more-comments hidden');
                            }
                        });
                    };
                    repliesCont.appendChild(loadMore);
                }
            }

            var c = document.createElement('div');
            var c1 = document.createElement('div');
            c1.setAttribute('style', 'display : inline-block;');
            c.appendChild(c1);
            c1.appendChild(img);
            c1.appendChild(updateCont);

            return c;
        }
    },
    button : {
        VOTE_TYPE_UPDATE : 'update',
        VOTE_TYPE_COMMENT : 'comment',
        vote1 : function(data) {
            var _data = {};

            data.ele.onclick = function() {
                console.log('dsa');
                if (data.beforeVote) {
                    if (!data.beforeVote(_data)) {
                        return;
                    }
                }

                console.log(_data);

                sAjax({
                    url : _data.url,
                    type : 'POST',
                    data : _data.data,
                    success : function(response) {
                        if (data.onVote) {
                            data.onVote(response);
                        }
                    }
                });
            };
return;
            var vote = document.createElement('span');
            vote.setAttribute('class', 'btn');

            var upvotes = document.createElement('span');
            upvotes.innerHTML = data.upvotes;
            upvotes.setAttribute('style', 'margin-left : 5px;');

            if (App.user.isLogged()) {
                vote.innerHTML = data.voted ? 'unvote' : 'upvote';

                vote.onclick = function() {
                    var url = '';

                    if (App.button.VOTE_TYPE_UPDATE === data.type) {
                        url = data.voted ? '/update/unvote' : '/update/upvote';
                    } else if (App.button.VOTE_TYPE_COMMENT === data.type) {
                        url = data.voted ? '/comment/unvote' : '/comment/upvote';
                    } else {
                        return;
                    }

                    sAjax({
                        url : url,
                        type : 'POST',
                        data : {
                            id : data.id
                        },
                        success : function(response) {
                            if (!response) {
                                return;
                            }

                            if (data.voted) {
                                data.voted = false;
                                vote.innerHTML = 'upvote';
                                upvotes.innerHTML = parseInt(upvotes.innerHTML) - 1;
                            } else {
                                data.voted = true;
                                vote.innerHTML = 'unvote';
                                upvotes.innerHTML = parseInt(upvotes.innerHTML) + 1;
                            }
                        }
                    });
                };
            } else {
                vote.innerHTML = 'upvotes';
            }

            var voteCont = document.createElement('span');
            voteCont.appendChild(vote);
            voteCont.appendChild(upvotes);

            return voteCont;
        },
        vote : function(data) {
            var vote = document.createElement('span');
            vote.setAttribute('class', 'btn');

            var upvotes = document.createElement('span');
            upvotes.innerHTML = data.upvotes;
            upvotes.setAttribute('style', 'margin-left : 5px;');

            if (App.user.isLogged()) {
                vote.innerHTML = data.voted ? 'unvote' : 'upvote';

                vote.onclick = function() {
                    var url = '';

                    if (App.button.VOTE_TYPE_UPDATE === data.type) {
                        url = data.voted ? '/update/unvote' : '/update/upvote';
                    } else if (App.button.VOTE_TYPE_COMMENT === data.type) {
                        url = data.voted ? '/comment/unvote' : '/comment/upvote';
                    } else {
                        return;
                    }

                    sAjax({
                        url : url,
                        type : 'POST',
                        data : {
                            id : data.id
                        },
                        success : function(response) {
                            if (!response) {
                                return;
                            }

                            if (data.voted) {
                                data.voted = false;
                                vote.innerHTML = 'upvote';
                                upvotes.innerHTML = parseInt(upvotes.innerHTML) - 1;
                            } else {
                                data.voted = true;
                                vote.innerHTML = 'unvote';
                                upvotes.innerHTML = parseInt(upvotes.innerHTML) + 1;
                            }
                        }
                    });
                };
            } else {
                vote.innerHTML = 'upvotes';
            }

            var voteCont = document.createElement('span');
            voteCont.appendChild(vote);
            voteCont.appendChild(upvotes);

            return voteCont;
        }
    }
};
