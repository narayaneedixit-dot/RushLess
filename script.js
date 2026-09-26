// RushLess - frontend logic
// Har page ka apna hissa hai. Jis page par woh element nahi hai,
// wahan uska code chalta hi nahi.

// Simple check to see if an email looks valid
function emailOk(email) {
    if (email.indexOf("@") < 1) {
        return false;
    }
    if (email.indexOf(".") < 0) {
        return false;
    }
    return true;
}

function serverDown(el) {
    el.innerHTML = "Could not reach the server. Open the site through http://localhost/ with Apache running.";
}

// ==================================================
// Login page (index.html)
// ==================================================
var loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.onsubmit = function (e) {
        e.preventDefault();

        var email = document.getElementById("email").value;
        var pass = document.getElementById("password").value;
        var msg = document.getElementById("msg");

        if (email == "" || pass == "") {
            msg.innerHTML = "Please enter your email and password.";
            return;
        }

        if (emailOk(email) == false) {
            msg.innerHTML = "Please enter a valid email address.";
            return;
        }

        msg.innerHTML = "Please wait...";

        var data = new FormData(loginForm);

        fetch("php/login.php", { method: "POST", body: data })
            .then(function (res) { return res.json(); })
            .then(function (reply) {

                if (reply.error) {
                    msg.innerHTML = reply.error;
                } else if (reply.role == "college") {
                    window.location.href = "college-dashboard.html";
                } else if (reply.role == "admin") {
                    window.location.href = "outlet-dashboard.html";
                } else {
                    window.location.href = "home.html";
                }
            })
            .catch(function () { serverDown(msg); });
    };
}

// ==================================================
// Student signup page
// ==================================================
var studentForm = document.getElementById("studentForm");

if (studentForm) {
    studentForm.onsubmit = function (e) {
        e.preventDefault();

        var name = document.getElementById("name").value;
        var email = document.getElementById("email").value;
        var roll = document.getElementById("roll_no").value;
        var branch = document.getElementById("branch").value;
        var sem = document.getElementById("semester").value;
        var pass = document.getElementById("password").value;
        var msg = document.getElementById("msg");

        if (name == "" || email == "" || roll == "" || branch == "" || sem == "" || pass == "") {
            msg.innerHTML = "Please fill in all the fields.";
            return;
        }

        if (emailOk(email) == false) {
            msg.innerHTML = "Please enter a valid email address.";
            return;
        }

        msg.innerHTML = "Please wait...";

        var data = new FormData(studentForm);

        fetch("php/signup_student.php", { method: "POST", body: data })
            .then(function (res) { return res.json(); })
            .then(function (reply) {
                if (reply.error) {
                    msg.innerHTML = reply.error;
                } else {
                    window.location.href = "home.html";
                }
            })
            .catch(function () { serverDown(msg); });
    };
}

// ==================================================
// Menu rows (college dashboard ka form)
// ==================================================
function addItem() {
    var list = document.getElementById("menuList");

    var row = document.createElement("div");
    row.className = "menu-row";
    row.innerHTML =
        '<input type="text" name="item_name[]" class="item-name" placeholder="Item name">' +
        '<input type="number" name="item_price[]" class="item-price" placeholder="Price">' +
        '<input type="number" name="item_prep[]" class="item-prep" placeholder="Prep min" step="0.5">' +
        '<select name="item_equipment[]" class="item-equipment"></select>' +
        '<button type="button" class="remove-btn" onclick="removeItem(this)">X</button>';

    list.appendChild(row);
    fillEquipOptions();
}

// ==================================================
// Equipment rows (college dashboard ka form)
// ==================================================
function addEquip() {
    var list = document.getElementById("equipList");

    var row = document.createElement("div");
    row.className = "menu-row";
    row.innerHTML =
        '<input type="text" name="equip_name[]" class="equip-name" placeholder="e.g. Dosa Tawa">' +
        '<input type="number" name="equip_qty[]" class="equip-qty" placeholder="Units" min="1" value="1">' +
        '<button type="button" class="remove-btn" onclick="removeEquip(this)">X</button>';

    list.appendChild(row);
    fillEquipOptions();
}

function removeEquip(btn) {
    var list = document.getElementById("equipList");

    if (list.children.length > 1) {
        list.removeChild(btn.parentNode);
        fillEquipOptions();
    }
}

/*
 * Har item ke dropdown mein upar likhe hue equipment ke naam bhar do.
 * Jo pehle se chuna hua tha woh bacha rehta hai, agar woh station abhi bhi list mein hai.
 */
function fillEquipOptions() {

    var names = document.getElementsByClassName("equip-name");
    var selects = document.getElementsByClassName("item-equipment");

    for (var i = 0; i < selects.length; i++) {

        var chosen = selects[i].value;
        var html = '<option value="">No equipment</option>';

        for (var j = 0; j < names.length; j++) {
            if (names[j].value.trim() != "") {
                html = html + '<option>' + names[j].value.trim() + '</option>';
            }
        }

        selects[i].innerHTML = html;
        selects[i].value = chosen;
    }
}

// Equipment ka naam likhte hi dropdown update ho jaye
document.addEventListener("input", function (e) {
    if (e.target && e.target.className == "equip-name") {
        fillEquipOptions();
    }
});

function removeItem(btn) {
    var list = document.getElementById("menuList");

    // At least one row should always stay on the page
    if (list.children.length > 1) {
        list.removeChild(btn.parentNode);
    }
}

// ==================================================
// College dashboard: register, edit and delete outlets
// ==================================================
var outletForm = document.getElementById("outletForm");

if (outletForm) {

    loadOutlets();

    outletForm.onsubmit = function (e) {
        e.preventDefault();

        var id = document.getElementById("outlet_id").value;
        var outlet = document.getElementById("outlet_name").value;
        var email = document.getElementById("email").value;
        var pass = document.getElementById("password").value;
        var msg = document.getElementById("msg");

        if (outlet == "" || email == "") {
            msg.innerHTML = "Please fill in the outlet name and email.";
            return;
        }

        // Password is needed for a new outlet, optional while editing
        if (id == "" && pass == "") {
            msg.innerHTML = "Please set a password for the outlet.";
            return;
        }

        if (emailOk(email) == false) {
            msg.innerHTML = "Please enter a valid email address.";
            return;
        }

        var names = document.getElementsByClassName("item-name");
        var prices = document.getElementsByClassName("item-price");
        var count = 0;

        for (var i = 0; i < names.length; i++) {
            if (names[i].value != "" && prices[i].value != "") {
                count = count + 1;
            }
        }

        if (count == 0) {
            msg.innerHTML = "Please add at least one menu item with its price.";
            return;
        }

        msg.innerHTML = "Please wait...";

        // FormData text fields aur photo dono bhej deta hai
        var data = new FormData(outletForm);
        var url = (id == "") ? "php/add_outlet.php" : "php/update_outlet.php";

        fetch(url, { method: "POST", body: data })
            .then(function (res) { return res.json(); })
            .then(function (reply) {
                if (reply.error) {
                    msg.innerHTML = reply.error;
                } else {
                    resetForm();
                    loadOutlets();
                }
            })
            .catch(function () { serverDown(msg); });
    };
}

// College ko dikhata hai: kaunsa outlet abhi Open hai aur uske kitne orders hain
function loadOutlets() {
    var table = document.getElementById("outletTable");

    fetch("php/get_all_outlets.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                table.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            if (reply.outlets.length == 0) {
                table.innerHTML = "<p class='small-text'>No outlets registered yet.</p>";
                return;
            }

            var html = "";

            for (var i = 0; i < reply.outlets.length; i++) {
                var o = reply.outlets[i];
                var status = (o.is_open == 1) ? "Open now" : "Closed";

                html = html +
                    '<div class="outlet-row">' +
                    '<div>' +
                    '<b>' + o.name + '</b><br>' +
                    '<span class="small-text">' + o.category + " - " + o.email + '</span><br>' +
                    '<span class="badge">' + status + '</span> ' +
                    '<span class="small-text">' + o.item_count + ' items, ' +
                    o.active_orders + ' active, ' + o.done_orders + ' done</span>' +
                    '</div>' +
                    '<div>' +
                    '<button class="add-btn" onclick="editOutlet(' + o.id + ')">Edit</button> ' +
                    '<button class="remove-btn wide" onclick="deleteOutlet(' + o.id + ')">Delete</button>' +
                    '</div>' +
                    '</div>';
            }

            table.innerHTML = html;
        })
        .catch(function () { serverDown(table); });
}

// Form ko ek outlet ki details se bhar do
function editOutlet(id) {

    fetch("php/get_outlet.php?id=" + id)
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                document.getElementById("msg").innerHTML = reply.error;
                return;
            }

            document.getElementById("outlet_id").value = reply.outlet.id;
            document.getElementById("category").value = reply.outlet.category;
            document.getElementById("outlet_name").value = reply.outlet.name;
            document.getElementById("email").value = reply.outlet.email;
            document.getElementById("password").value = "";

            document.getElementById("formTitle").innerHTML = "Edit Outlet";
            document.getElementById("saveBtn").innerHTML = "Save Changes";
            document.getElementById("cancelBtn").style.display = "inline-block";
            document.getElementById("passNote").innerHTML = "Leave the password empty to keep the old one.";

            // Pehle equipment, kyunki item ka dropdown usi se banta hai
            var eqList = document.getElementById("equipList");
            eqList.innerHTML = "";

            var equipment = reply.equipment ? reply.equipment : [];

            for (var e = 0; e < equipment.length; e++) {
                addEquip();
                document.getElementsByClassName("equip-name")[e].value = equipment[e].name;
                document.getElementsByClassName("equip-qty")[e].value = equipment[e].quantity;
            }

            if (equipment.length == 0) {
                addEquip();
            }

            var list = document.getElementById("menuList");
            list.innerHTML = "";

            for (var i = 0; i < reply.items.length; i++) {
                addItem();
                document.getElementsByClassName("item-name")[i].value = reply.items[i].name;
                document.getElementsByClassName("item-price")[i].value = reply.items[i].price;
                document.getElementsByClassName("item-prep")[i].value = reply.items[i].prep_min;
            }

            fillEquipOptions();

            for (var i = 0; i < reply.items.length; i++) {
                if (reply.items[i].equipment_name) {
                    document.getElementsByClassName("item-equipment")[i].value = reply.items[i].equipment_name;
                }
            }

            if (reply.items.length == 0) {
                addItem();
            }

            window.scrollTo(0, document.body.scrollHeight);
        })
        .catch(function () { serverDown(document.getElementById("msg")); });
}

function deleteOutlet(id) {

    if (confirm("Delete this outlet, its menu and its login account?") == false) {
        return;
    }

    var data = new FormData();
    data.append("outlet_id", id);

    fetch("php/delete_outlet.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {
            if (reply.error) {
                document.getElementById("msg").innerHTML = reply.error;
            } else {
                resetForm();
                loadOutlets();
            }
        })
        .catch(function () { serverDown(document.getElementById("msg")); });
}

// Wapas khaali "naya outlet" wale mode mein
function resetForm() {
    outletForm.reset();

    document.getElementById("outlet_id").value = "";
    document.getElementById("formTitle").innerHTML = "Register an Outlet";
    document.getElementById("saveBtn").innerHTML = "Register Outlet";
    document.getElementById("cancelBtn").style.display = "none";
    document.getElementById("passNote").innerHTML = "";
    document.getElementById("msg").innerHTML = "";

    var eqList = document.getElementById("equipList");
    eqList.innerHTML = "";
    addEquip();

    var list = document.getElementById("menuList");
    list.innerHTML = "";
    addItem();
}

// ==================================================
// Canteen page: saare canteen outlets
// ==================================================
var outletList = document.getElementById("outletList");

if (outletList) {
    fetch("php/get_outlets.php?category=canteen")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (!reply.outlets || reply.outlets.length == 0) {
                outletList.innerHTML = "<p>No outlets yet. The college has to register one first.</p>";
                return;
            }

            var html = "";

            for (var i = 0; i < reply.outlets.length; i++) {
                var o = reply.outlets[i];
                var status = (o.is_open == 1) ? "Open now" : "Closed";
                var photo = o.photo ? '<img src="' + o.photo + '" class="outlet-photo">' : '<div class="outlet-photo"></div>';

                html = html +
                    '<a class="outlet-card" href="outlet.html?id=' + o.id + '">' +
                    photo +
                    '<h3>' + o.name + '</h3>' +
                    '<p>' + o.item_count + ' items on the menu</p>' +
                    '<p class="small-text">' + ratingLabel(o) + '</p>' +
                    '<p class="badge">' + status + '</p>' +
                    '</a>';
            }

            outletList.innerHTML = html;
        })
        .catch(function () { serverDown(outletList); });
}

// ==================================================
// Outlet page: menu, cart aur order
// ==================================================
var menuList2 = document.getElementById("menuList2");
var cart = {};          // item id -> { name, price, qty }
var outletId = null;

if (menuList2) {

    // Outlet ki id address bar se aati hai, jaise outlet.html?id=3
    outletId = new URLSearchParams(window.location.search).get("id");

    fetch("php/get_menu.php?outlet_id=" + outletId)
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                menuList2.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            document.getElementById("outletName").innerHTML =
                reply.outlet.name + (reply.outlet.is_open == 1 ? "" : " (Closed)");

            var html = "";

            for (var i = 0; i < reply.items.length; i++) {
                var item = reply.items[i];

                var right = "";

                if (item.is_available == 1) {
                    right = 'Rs ' + item.price + ' &nbsp; <span class="small-text">' + item.prep_min + ' min</span> &nbsp; ' +
                        '<button class="add-btn" onclick="addToCart(' + item.id + ",'" + item.name + "'," + item.price + ')">Add</button>';
                } else {
                    right = '<span class="small-text">Out of stock</span>';
                }

                html = html +
                    '<div class="menu-item' + (item.is_available == 1 ? '' : ' faded') + '">' +
                    '<span>' + item.name + '</span>' +
                    '<span>' + right + '</span>' +
                    '</div>';
            }

            menuList2.innerHTML = html;

            // Search se aaye ho to wahi item pehle se cart mein daal dete hain
            var wanted = new URLSearchParams(window.location.search).get("item");

            if (wanted) {
                for (var k = 0; k < reply.items.length; k++) {
                    if (reply.items[k].id == wanted && reply.items[k].is_available == 1) {
                        addToCart(reply.items[k].id, reply.items[k].name, reply.items[k].price);
                    }
                }
            }

            showCart();
        })
        .catch(function () { serverDown(menuList2); });

    document.getElementById("proceedBtn").onclick = placeOrder;

    // Order karne ke liye student login zaroori hai, isliye pehle hi bata dete hain
    fetch("php/me.php")
        .then(function (res) { return res.json(); })
        .then(function (me) {
            if (me.role != "student") {
                document.getElementById("msg").innerHTML =
                    "You are not logged in as a student. <a href='index.html'>Log in</a> to place an order.";
            }
        });
}

function addToCart(id, name, price) {

    if (cart[id]) {
        cart[id].qty = cart[id].qty + 1;
    } else {
        cart[id] = { name: name, price: price, qty: 1 };
    }

    showCart();
}

function removeFromCart(id) {

    cart[id].qty = cart[id].qty - 1;

    if (cart[id].qty == 0) {
        delete cart[id];
    }

    showCart();
}

function showCart() {
    var lines = document.getElementById("cartLines");
    var ids = Object.keys(cart);

    if (ids.length == 0) {
        lines.innerHTML = "<p class='small-text'>No items added yet.</p>";
        return;
    }

    var html = "";
    var total = 0;

    for (var i = 0; i < ids.length; i++) {
        var id = ids[i];
        var line = cart[id];
        total = total + line.price * line.qty;

        html = html +
            '<div class="cart-line">' +
            '<span>' + line.name + ' x ' + line.qty + '</span>' +
            '<span>Rs ' + (line.price * line.qty).toFixed(0) + ' ' +
            '<button class="remove-btn" onclick="removeFromCart(' + id + ')">-</button>' +
            '</span>' +
            '</div>';
    }

    html = html + '<div class="cart-line total"><span>Total</span><span>Rs ' + total.toFixed(0) + '</span></div>';
    lines.innerHTML = html;
}

// Cart PHP ko bhejo, wahan order save hota hai aur ready time predict hota hai
function placeOrder() {
    var msg = document.getElementById("msg");
    var ids = Object.keys(cart);

    if (ids.length == 0) {
        msg.innerHTML = "Please add at least one item.";
        return;
    }

    // PHP ko sirf item id aur qty chahiye, price wahan database se aata hai
    var items = [];

    for (var i = 0; i < ids.length; i++) {
        items.push({ item_id: ids[i], qty: cart[ids[i]].qty });
    }

    msg.innerHTML = "Please wait...";

    var data = new FormData();
    data.append("outlet_id", outletId);
    data.append("cart", JSON.stringify(items));

    fetch("php/place_order.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                msg.innerHTML = reply.error;
                return;
            }

            msg.innerHTML = "";
            cart = {};
            showCart();
            showToken(reply);
        })
        .catch(function () { serverDown(msg); });
}

// Token aur predicted time ka poora hisaab
function showToken(r) {
    var label = (r.is_provisional == 1) ? "Provisional Estimate" : "Based on recent orders";

    document.getElementById("tokenBox").innerHTML =
        '<div class="token-box">' +
        '<p class="small-text">Your token</p>' +
        '<h1 class="token">' + r.token + '</h1>' +
        '<p>Expected ready at <b>' + r.ready_at + '</b> (about ' + r.predicted_min + ' min)</p>' +
        '<p class="badge">' + label + '</p>' +
        '<div class="breakdown">' +
        '<div><span>Queue: ' + r.queue_count + ' order(s) x ' + r.avg_service_min + ' min</span><span>' + r.queue_min + ' min</span></div>' +
        '<div><span>Rush hour (' + r.slot_hour + ':00 slot)</span><span>+ ' + r.slot_min + ' min</span></div>' +
        '<div><span>Your items</span><span>+ ' + r.order_min + ' min</span></div>' +
        '<div class="total"><span>Total</span><span>' + r.predicted_min + ' min</span></div>' +
        '</div>' +
        '<p class="small-text">Amount to pay: Rs ' + r.amount + '</p>' +
        '<p class="small-text"><a href="track.html">Track this order</a></p>' +
        '</div>';
}

// ==================================================
// Outlet dashboard: open/close aur live queue
// ==================================================
var queueBox = document.getElementById("queueBox");

if (queueBox) {

    loadQueue();
    loadStats();

    // Har 5 second mein queue aur stats dobara le aao
    setInterval(loadQueue, 5000);
    setInterval(loadStats, 5000);

    document.getElementById("toggleBtn").onclick = function () {

        fetch("php/toggle_outlet.php", { method: "POST" })
            .then(function (res) { return res.json(); })
            .then(function (reply) {
                if (reply.error) {
                    document.getElementById("msg").innerHTML = reply.error;
                } else {
                    loadQueue();
                }
            })
            .catch(function () { serverDown(document.getElementById("msg")); });
    };
}

// Outlet dashboard ke upar aaj ke numbers
function loadStats() {
    var row = document.getElementById("statsRow");

    fetch("php/outlet_stats.php")
        .then(function (res) { return res.json(); })
        .then(function (s) {

            if (s.error) {
                row.innerHTML = "";
                return;
            }

            var avgText = s.records > 0 ? s.avg_service_min + " min" : "--";
            var note = s.provisional == 1
                ? "baseline abhi (" + s.records + "/3 records)"
                : "last " + s.records + " orders ka average";

            row.innerHTML =
                '<div class="stat-box"><span class="stat-num">' + s.today_orders + '</span>' +
                '<span class="stat-label">Orders today</span></div>' +
                '<div class="stat-box"><span class="stat-num">' + s.waiting + '</span>' +
                '<span class="stat-label">Waiting right now</span></div>' +
                '<div class="stat-box"><span class="stat-num">' + avgText + '</span>' +
                '<span class="stat-label">Avg service time</span>' +
                '<span class="small-text">' + note + '</span></div>';
        })
        .catch(function () { row.innerHTML = ""; });
}

function loadQueue() {

    fetch("php/outlet_orders.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                queueBox.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            document.getElementById("outletTitle").innerHTML = reply.outlet.name;
            document.getElementById("statusText").innerHTML =
                (reply.outlet.is_open == 1) ? "Status: Open" : "Status: Closed";
            document.getElementById("toggleBtn").innerHTML =
                (reply.outlet.is_open == 1) ? "Close outlet" : "Open outlet";

            if (reply.orders.length == 0) {
                queueBox.innerHTML = "<p class='small-text'>No orders waiting.</p>";
                return;
            }

            var html = "";

            for (var i = 0; i < reply.orders.length; i++) {
                var o = reply.orders[i];
                var button = "";

                if (o.status == "pending") {
                    button = '<button class="add-btn" onclick="setStatus(' + o.id + ",'start')" + '">Start</button>';
                } else if (o.status == "preparing") {
                    button = '<button class="add-btn" onclick="setStatus(' + o.id + ",'ready')" + '">Mark Ready</button>';
                } else if (o.status == "ready") {
                    button = '<button class="add-btn" onclick="setStatus(' + o.id + ",'collect')" + '">Collected</button>';
                }

                html = html +
                    '<div class="outlet-row">' +
                    '<div>' +
                    '<b>' + o.token + '</b> - ' + o.items + '<br>' +
                    '<span class="status-tag ' + o.status + '">' + o.status + '</span> ' +
                    (o.source == "walkin" ? '<span class="tag-walkin">walk-in</span> ' : '') +
                    '<span class="small-text">' + o.student + " (" + o.roll_no + ") - " +
                    'predicted ' + o.predicted_min + ' min</span>' +
                    '</div>' +
                    '<div>' + button + '</div>' +
                    '</div>';
            }

            queueBox.innerHTML = html;
        })
        .catch(function () { serverDown(queueBox); });
}

// Start / Mark Ready / Collected
function setStatus(orderId, action) {

    var data = new FormData();
    data.append("order_id", orderId);
    data.append("action", action);

    fetch("php/update_order_status.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {
            if (reply.error) {
                document.getElementById("msg").innerHTML = reply.error;
            } else {
                document.getElementById("msg").innerHTML = "";
                loadQueue();
            }
        })
        .catch(function () { serverDown(document.getElementById("msg")); });
}

// ==================================================
// Track page: student ke apne orders, live
// ==================================================
var myOrders = document.getElementById("myOrders");

if (myOrders) {

    loadMyOrders();
    setInterval(loadMyOrders, 5000);
}

function loadMyOrders() {

    fetch("php/my_orders.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                myOrders.innerHTML = "<p>" + reply.error + " <a href='index.html'>Log in</a></p>";
                return;
            }

            if (reply.orders.length == 0) {
                myOrders.innerHTML = "<p class='small-text'>You have not placed any order yet.</p>";
                return;
            }

            var html = "";
            var readyTokens = [];

            for (var i = 0; i < reply.orders.length; i++) {
                var o = reply.orders[i];
                var line = "";

                if (o.status == "ready") {
                    readyTokens.push(o.token);
                }

                // Status ke hisaab se student ko seedhi baat batao
                if (o.status == "pending") {
                    line = "Waiting in queue - " + o.ahead + " order(s) ahead of you";
                } else if (o.status == "preparing") {
                    line = "Being prepared right now";
                } else if (o.status == "ready") {
                    line = "Ready - please collect it from the counter";
                } else {
                    line = "Collected";
                }

                // Order collect ho gaya to rating ka option dikhao
                var rateArea = "";

                if (o.status == "collected") {
                    if (o.my_rating) {
                        rateArea = '<div class="rate-area">Your rating: ' +
                                   starText(o.my_rating) + '</div>';
                    } else {
                        rateArea = '<div class="rate-area" id="rate-' + o.token + '">' +
                                   '<span class="small-text">Rate this order:</span> ' +
                                   starButtons(o.token) + '</div>';
                    }
                }

                html = html +
                    '<div class="outlet-row">' +
                    '<div>' +
                    '<b>' + o.token + '</b> - ' + o.outlet + '<br>' +
                    '<span class="small-text">' + o.items + '</span><br>' +
                    '<span class="small-text">' + line + '</span>' +
                    rateArea +
                    '</div>' +
                    '<div class="status-cell">' +
                    '<span class="status-tag ' + o.status + '">' + o.status + '</span><br>' +
                    '<span class="small-text">Rs ' + o.amount + '</span>' +
                    '</div>' +
                    '</div>';
            }

            myOrders.innerHTML = html;
            showReadyAlert(readyTokens);
        })
        .catch(function () { serverDown(myOrders); });
}

// Upar wali patti: kaunsa token counter par ready pada hai
function showReadyAlert(tokens) {
    var box = document.getElementById("readyAlert");

    if (!box) {
        return;
    }

    if (tokens.length == 0) {
        box.innerHTML = "";
        return;
    }

    box.innerHTML =
        '<div class="ready-alert">' +
        '<b>' + tokens.join(", ") + '</b> ready hai. Counter se le lo.' +
        '</div>';
}

// ==================================================
// Outlet dashboard: apna menu edit karna
// ==================================================
var myMenu = document.getElementById("myMenu");

if (myMenu) {

    loadMyMenu();

    document.getElementById("menuForm").onsubmit = function (e) {
        e.preventDefault();

        var msg = document.getElementById("menuMsg");
        msg.innerHTML = "Please wait...";

        var data = new FormData(document.getElementById("menuForm"));

        fetch("php/save_menu.php", { method: "POST", body: data })
            .then(function (res) { return res.json(); })
            .then(function (reply) {
                if (reply.error) {
                    msg.innerHTML = reply.error;
                } else {
                    msg.innerHTML = "Menu saved.";
                    loadMyMenu();
                }
            })
            .catch(function () { serverDown(msg); });
    };
}

function loadMyMenu() {

    fetch("php/outlet_menu.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                myMenu.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            myEquipment = reply.equipment ? reply.equipment : [];

            var html = "";

            for (var i = 0; i < reply.items.length; i++) {
                var it = reply.items[i];
                var stockText = (it.is_available == 1) ? "Mark out of stock" : "Back in stock";

                html = html +
                    '<div class="menu-row">' +
                    '<input type="hidden" name="item_id[]" value="' + it.id + '">' +
                    '<input type="text" name="item_name[]" class="item-name" value="' + it.name + '">' +
                    '<input type="number" name="item_price[]" class="item-price" value="' + it.price + '">' +
                    '<input type="number" name="item_prep[]" class="item-prep" step="0.5" value="' + it.prep_min + '">' +
                    equipSelect(it.equipment_id) +
                    '<button type="button" class="add-btn" onclick="toggleItem(' + it.id + ')">' + stockText + '</button>' +
                    '<button type="button" class="remove-btn" onclick="removeMenuRow(this)">X</button>' +
                    '</div>';
            }

            myMenu.innerHTML = html;

            if (reply.items.length == 0) {
                addMenuRow();
            }
        })
        .catch(function () { serverDown(myMenu); });
}

/*
 * Outlet dashboard ke menu row ka station dropdown.
 * Yahan value station ki id hoti hai, kyunki station pehle se database mein hai.
 */
var myEquipment = [];

function equipSelect(selectedId) {

    var html = '<select name="item_equipment_id[]" class="item-equipment">' +
               '<option value="">No equipment</option>';

    for (var i = 0; i < myEquipment.length; i++) {
        var e = myEquipment[i];
        var chosen = (selectedId && e.id == selectedId) ? " selected" : "";

        html = html + '<option value="' + e.id + '"' + chosen + '>' +
               e.name + ' (' + e.quantity + ')</option>';
    }

    return html + '</select>';
}

// Nayi khaali row (iski id khaali hoti hai, isliye PHP use insert kar deta hai)
function addMenuRow() {
    var row = document.createElement("div");
    row.className = "menu-row";
    row.innerHTML =
        '<input type="hidden" name="item_id[]" value="">' +
        '<input type="text" name="item_name[]" class="item-name" placeholder="Item name">' +
        '<input type="number" name="item_price[]" class="item-price" placeholder="Price">' +
        '<input type="number" name="item_prep[]" class="item-prep" step="0.5" placeholder="Prep min">' +
        equipSelect(null) +
        '<button type="button" class="remove-btn" onclick="removeMenuRow(this)">X</button>';

    myMenu.appendChild(row);
}

function removeMenuRow(btn) {
    myMenu.removeChild(btn.parentNode);

    if (myMenu.children.length == 0) {
        addMenuRow();
    }
}

// Out of stock / back in stock
function toggleItem(itemId) {

    var data = new FormData();
    data.append("item_id", itemId);

    fetch("php/toggle_item.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {
            if (reply.error) {
                document.getElementById("menuMsg").innerHTML = reply.error;
            } else {
                loadMyMenu();
            }
        })
        .catch(function () { serverDown(document.getElementById("menuMsg")); });
}


// ==================================================
// Phase 2 — Walk-in order (outlet dashboard)
// ==================================================
var walkinMenu = document.getElementById("walkinMenu");
var walkinCart = {};

if (walkinMenu) {

    loadWalkinMenu();
    document.getElementById("walkinBtn").onclick = saveWalkinOrder;
}

// Apna hi menu, par yahan Add button ke saath
function loadWalkinMenu() {

    fetch("php/outlet_menu.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                walkinMenu.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            var html = "";

            for (var i = 0; i < reply.items.length; i++) {
                var it = reply.items[i];

                if (it.is_available == 0) {
                    continue;                      // out of stock item walk-in mein bhi nahi
                }

                html = html +
                    '<div class="menu-item">' +
                    '<span>' + it.name + '</span>' +
                    '<span>Rs ' + it.price + ' &nbsp; ' +
                    '<button type="button" class="add-btn" onclick="addWalkin(' + it.id + ",'" + it.name + "'," + it.price + ')">Add</button>' +
                    '</span>' +
                    '</div>';
            }

            walkinMenu.innerHTML = html;
            showWalkinCart();
        })
        .catch(function () { serverDown(walkinMenu); });
}

function addWalkin(id, name, price) {

    if (walkinCart[id]) {
        walkinCart[id].qty = walkinCart[id].qty + 1;
    } else {
        walkinCart[id] = { name: name, price: price, qty: 1 };
    }

    showWalkinCart();
}

function removeWalkin(id) {

    walkinCart[id].qty = walkinCart[id].qty - 1;

    if (walkinCart[id].qty == 0) {
        delete walkinCart[id];
    }

    showWalkinCart();
}

function showWalkinCart() {
    var box = document.getElementById("walkinCart");
    var ids = Object.keys(walkinCart);

    if (ids.length == 0) {
        box.innerHTML = "<p class='small-text'>No items added yet.</p>";
        return;
    }

    var html = "";
    var total = 0;

    for (var i = 0; i < ids.length; i++) {
        var line = walkinCart[ids[i]];
        total = total + line.price * line.qty;

        html = html +
            '<div class="cart-line">' +
            '<span>' + line.name + ' x ' + line.qty + '</span>' +
            '<span>Rs ' + (line.price * line.qty).toFixed(0) + ' ' +
            '<button type="button" class="remove-btn" onclick="removeWalkin(' + ids[i] + ')">-</button>' +
            '</span></div>';
    }

    html = html + '<div class="cart-line total"><span>Total</span><span>Rs ' + total.toFixed(0) + '</span></div>';
    box.innerHTML = html;
}

function saveWalkinOrder() {
    var msg = document.getElementById("walkinMsg");
    var ids = Object.keys(walkinCart);

    if (ids.length == 0) {
        msg.innerHTML = "Please add at least one item.";
        return;
    }

    var items = [];

    for (var i = 0; i < ids.length; i++) {
        items.push({ item_id: ids[i], qty: walkinCart[ids[i]].qty });
    }

    msg.innerHTML = "Please wait...";

    var data = new FormData();
    data.append("cart", JSON.stringify(items));

    fetch("php/walkin_order.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                msg.innerHTML = reply.error;
                return;
            }

            msg.innerHTML = "Token " + reply.token + " added. Ready by " + reply.ready_at +
                            " (about " + reply.predicted_min + " min).";
            walkinCart = {};
            showWalkinCart();
            loadQueue();
            loadStats();
        })
        .catch(function () { serverDown(msg); });
}

// ==================================================
// Phase 2 — Accuracy (college dashboard)
// ==================================================
var accuracyBox = document.getElementById("accuracyBox");

if (accuracyBox) {
    loadAccuracy();
}

function loadAccuracy() {

    fetch("php/accuracy.php")
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                accuracyBox.innerHTML = "<p>" + reply.error + "</p>";
                return;
            }

            if (reply.count == 0) {
                accuracyBox.innerHTML = "<p class='small-text'>No orders have been collected yet.</p>";
                return;
            }

            var html = '<div class="stat-box" style="max-width:260px;margin-bottom:12px">' +
                       '<span class="stat-num">' + reply.avg_error_min + ' min</span>' +
                       '<span class="stat-label">Average error (' + reply.count + ' orders)</span></div>';

            html = html + '<div class="acc-table">' +
                   '<div class="acc-row acc-head"><span>Token</span><span>Outlet</span>' +
                   '<span>Predicted</span><span>Actual</span><span>Error</span></div>';

            for (var i = 0; i < reply.orders.length; i++) {
                var o = reply.orders[i];
                var sign = o.error_min > 0 ? "+" : "";

                html = html +
                    '<div class="acc-row">' +
                    '<span>' + o.token + (o.source == "walkin" ? ' <span class="tag-walkin">walk-in</span>' : '') + '</span>' +
                    '<span>' + o.outlet + '</span>' +
                    '<span>' + o.predicted_min + ' min</span>' +
                    '<span>' + o.actual_min + ' min</span>' +
                    '<span>' + sign + o.error_min + ' min</span>' +
                    '</div>';
            }

            accuracyBox.innerHTML = html + '</div>';
        })
        .catch(function () { serverDown(accuracyBox); });
}


// ==================================================
// FAQ box — student diye hue sawaal par button dabata hai (koi AI nahi).
// Har button ek fixed sawaal php/chat.php ko bhejta hai, jo database se
// live jawab banata hai.
// ==================================================
var chatBody = document.getElementById("chatBody");

if (chatBody) {

    addChat("bot", "Choose a question below.");

    var chatBox = document.getElementById("chatBox");
    var chatMinimize = document.getElementById("chatMinimize");

    if (chatBox && chatMinimize) {
        chatMinimize.onclick = function () {
            var minimized = chatBox.classList.toggle("is-minimized");
            chatMinimize.textContent = minimized ? "+" : "−";
            chatMinimize.setAttribute("aria-expanded", String(!minimized));
            chatMinimize.setAttribute("aria-label", minimized ? "Expand" : "Minimize");
            chatMinimize.title = minimized ? "Expand" : "Minimize";
        };
    }

    // Har FAQ button ka click
    var faqButtons = document.getElementsByClassName("faq-btn");

    for (var f = 0; f < faqButtons.length; f++) {
        faqButtons[f].onclick = function () {
            askFaq(this.getAttribute("data-q"), this.textContent);
        };
    }
}

// Ek line chat mein jodo
function addChat(who, text) {
    var div = document.createElement("div");
    div.className = "chat-line " + who;
    div.innerHTML = text;
    chatBody.appendChild(div);
    chatBody.scrollTop = chatBody.scrollHeight;
}

// FAQ button: sawaal dikhao, phir database se jawab laao
function askFaq(key, label) {

    addChat("me", label);

    var data = new FormData();
    data.append("message", key);

    fetch("php/chat.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {
            addChat("bot", reply.reply ? reply.reply : reply.error);
        })
        .catch(function () {
            addChat("bot", "Unable to reach the server. Please try again.");
        });
}


// ==================================================
// Food search — ek item poore campus mein kahan mil raha hai
// ==================================================
var searchResults = document.getElementById("searchResults");

if (searchResults) {

    document.getElementById("searchBtn").onclick = runSearch;

    document.getElementById("searchText").onkeydown = function (e) {
        if (e.key == "Enter") {
            runSearch();
        }
    };

    // Agar URL mein ?q= aaya ho to seedha search chala do
    var q = new URLSearchParams(window.location.search).get("q");

    if (q) {
        document.getElementById("searchText").value = q;
        runSearch();
    }
}

function runSearch() {
    var text = document.getElementById("searchText").value.trim();
    var msg = document.getElementById("searchMsg");

    if (text.length < 2) {
        msg.innerHTML = "Please enter at least 2 characters.";
        return;
    }

    msg.innerHTML = "";
    searchResults.innerHTML = "<p class='small-text'>Searching...</p>";

    fetch("php/search_items.php?q=" + encodeURIComponent(text))
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                searchResults.innerHTML = "";
                msg.innerHTML = reply.error;
                return;
            }

            if (reply.count == 0) {
                searchResults.innerHTML =
                    "<p class='small-text'>\"" + reply.query + "\" is not currently in stock at any open outlet.</p>";
                return;
            }

            // Sabse jaldi milne wala sabse upar (server se already sorted aata hai)
            var html = "<p class='small-text'>" + reply.count +
                       " outlet(s) found. Results are sorted by estimated wait time.</p>";

            for (var i = 0; i < reply.results.length; i++) {
                var r = reply.results[i];
                var tag = r.is_provisional == 1
                    ? '<span class="badge">Provisional</span>'
                    : '<span class="badge">Based on recent orders</span>';

                html = html +
                    '<div class="search-row">' +
                    '<div>' +
                    '<b>' + r.item_name + '</b> — ' + r.outlet_name + ' ' + ratingLabel(r) + '<br>' +
                    '<span class="small-text">Rs ' + Number(r.price).toFixed(0) +
                    ' · <span class="status-tag ready">In stock</span> · ' +
                    'queue ' + r.queue_count + ' order(s)</span><br>' +
                    '<span class="small-text">' + waitText(r) +
                    ' + rush ' + r.slot_min + ' min + item ' + r.order_min + ' min' +
                    '</span>' +
                    '</div>' +
                    '<div class="search-eta">' +
                    '<span class="stat-num">' + r.predicted_min + ' min</span><br>' +
                    '<span class="small-text">ready by ' + r.ready_at + '</span><br>' +
                    tag + '<br>' +
                    '<a class="btn-link" href="outlet.html?id=' + r.outlet_id + '&item=' + r.item_id + '">Order here</a>' +
                    '</div>' +
                    '</div>';
            }

            searchResults.innerHTML = html;
        })
        .catch(function () { serverDown(searchResults); });
}


// ==================================================
// Rating — collect hone ke baad student 1 se 5 star deta hai
// ==================================================

// Bhare hue aur khaali star, sirf dikhane ke liye
function starText(stars) {
    var out = "";

    for (var i = 1; i <= 5; i++) {
        out = out + (i <= stars ? "&#9733;" : "&#9734;");
    }

    return '<span class="stars">' + out + '</span>';
}

// Paanch dabane wale star
function starButtons(token) {
    var out = "";

    for (var i = 1; i <= 5; i++) {
        out = out + '<span class="star-btn" onclick="rateOrder(\'' + token + '\', ' + i + ')">&#9734;</span>';
    }

    return out;
}

function rateOrder(token, stars) {
    var box = document.getElementById("rate-" + token);

    if (box) {
        box.innerHTML = "<span class='small-text'>Saving...</span>";
    }

    var data = new FormData();
    data.append("token", token);
    data.append("stars", stars);

    fetch("php/rate_order.php", { method: "POST", body: data })
        .then(function (res) { return res.json(); })
        .then(function (reply) {

            if (reply.error) {
                if (box) {
                    box.innerHTML = "<span class='error'>" + reply.error + "</span>";
                }
                return;
            }

            if (box) {
                box.innerHTML = "Your rating: " + starText(reply.stars);
            }

            loadMyOrders();   // list refresh
        })
        .catch(function () {
            if (box) {
                box.innerHTML = "<span class='error'>Could not save your rating.</span>";
            }
        });
}


// Search result mein outlet ki rating
function ratingLabel(r) {

    // Kam se kam 3 rating ke baad hi average dikhate hain, warna ek galat
    // rating poore item/outlet ko kharab dikha deti hai.
    if (!r.avg_rating || r.rating_count < 3) {
        return '<span class="small-text">(not enough ratings)</span>';
    }

    return starText(Math.round(r.avg_rating)) +
           '<span class="small-text"> ' + r.avg_rating + ' (' + r.rating_count + ')</span>';
}


/*
 * Wait kis wajah se hai — counter ki line, ya machine par pada kaam.
 * Jo bada hota hai wahi asli rukawat hai, isliye wahi dikhate hain.
 */
function waitText(r) {

    if (r.equipment_min > r.queue_min) {
        return "Wait " + r.equipment_min + " min (" + r.equipment_name + " busy)";
    }

    return "Queue " + r.queue_min + " min";
}
