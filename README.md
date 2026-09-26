# RushLess — Campus Canteen Queue Prediction

RushLess tells a student when their canteen order will be ready, not just their place in the queue.

Plain HTML, CSS, JavaScript, PHP and MySQL. No frameworks, no external APIs, no AI/ML.
Runs fully offline on XAMPP (Apache + MySQL).

## Folder structure

```
Rushless/
├── index.html               Login
├── student-signup.html      Student sign up
├── home.html                Student home
├── canteen.html             Outlet list + food search
├── outlet.html              Menu, cart, place order
├── track.html               Order status + rating
├── search.html              Search one item across outlets
├── college-dashboard.html   College: register outlets, accuracy
├── outlet-dashboard.html    Outlet: live queue, walk-ins, menu
├── create-account.html
├── style.css
├── script.js                All frontend logic (fetch -> php/)
├── php/                     Backend, one file per request (JSON)
│   ├── db.php               Connection, session, JSON helpers
│   ├── predict_lib.php      Prediction engine
│   └── ...
├── uploads/                 Outlet photos
├── database.sql             Full schema + college account
└── update.sql               Changes for an older database
```

## Prediction

```
ETA = max(Queue Workload, Equipment Wait) + Slot Adjustment + Order Workload
```

- Queue Workload = active orders x average service time (last 20 finished orders)
- Equipment Wait = work waiting on the item's station / units of that station
- Slot Adjustment = extra minutes for the current rush hour
- Order Workload = longest item prep + 0.5 x (total qty - 1)

Every collected order saves its real service time, so the next estimate improves.

## Run locally

1. Put the `Rushless` folder in `C:\xampp\htdocs\`
2. Start Apache and MySQL in XAMPP
3. phpMyAdmin -> Import -> `database.sql`
4. Open `http://localhost/Rushless/`

College login: `college@rushless.com` / `college123`
