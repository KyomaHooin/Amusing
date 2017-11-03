//
// WIZ110SR Case
//
// TODO:
//
// - top vent
// - lip lock
// - mount top spacer

$fn=50;

//-----------------

boxThick=2;
wizLength=63;
wizWidth=45;
wizThick=1.5;
bottomMountHeight=1.3;
boxHeight=10;

//-----------------

module rounded_rect(x, y, z, radius) {
    linear_extrude(height=z)
        minkowski() {
            square([x,y]);
            circle(r = radius);
        }
}

module wiz_hole() {
    cylinder(h=wizThick+2, d=3);
}

module bottom_hole() {
    cylinder(h=boxThick+2, d=3);
}

module bottom_mount() {
    difference() {
        cylinder(h=bottomMountHeight, r1=3,r2=2.5);
        translate([0,0,-1]) cylinder(h=3, r1=1.5,r2=1.5);
    }
}

module bottom_sink() {
    difference() {
        cylinder(h=bottomMountHeight, r1=3,r2=1.5);
        translate([0,0,-1]) cylinder(h=3, r1=1.5,r2=1.5);
    }
}

module top_spacer() {
    difference() {
        cylinder(h=2*boxHeight-2*boxThick-wizThick, d=5);
//        translate([0,0,-6]) cylinder(h=boxHeight-boxThick-wizThick+2, d=2.5);
    }
}

module wall() {
    difference(){
        cube([10,16,2]);
        translate([5,8,-1]) cylinder(h=4,d=6);
        union(){
            translate([5,3.5,-1]) cylinder(h=4,d=3);
            translate([5,12.5,-1]) cylinder(h=4,d=3);
            translate([3.5,3.5,-1]) cube([3,9,4]);
        }
    }    
}

//-----------------

module top() {
    difference(){
        translate([0,0,0]) rounded_rect(wizWidth,wizLength,boxHeight,2);
        translate([0,0,-boxThick]) cube([wizWidth,wizLength,boxHeight]);// FILLER
        translate([0,0,-boxHeight]) wiz();// WIZZ
    }
    translate([3.5,3.5,wizThick]) top_spacer();// TOP SPACER
    translate([wizWidth-3.5,3.5,wizThick]) top_spacer();
    translate([3.5,wizLength-3.5,wizThick]) top_spacer();
    translate([wizWidth-3.5,wizLength-3.5,wizThick]) top_spacer();        
}

//-----------------

module bottom() {
    difference(){
        rounded_rect(wizWidth,wizLength,boxHeight,2);
        translate([0,0,2]) cube([wizWidth,wizLength,boxHeight]);// FILLER
        translate([0,0,boxThick+bottomMountHeight]) wiz();// WIZZ
        translate([3.5,3.5,-1]) bottom_hole();// BOTTOM HOLE
        translate([wizWidth-3.5,3.5,-1]) bottom_hole();
        translate([3.5,wizLength-3.5,-1]) bottom_hole();
        translate([wizWidth-3.5,wizLength-3.5,-1]) bottom_hole();
        translate([3.5,3.5,0]) bottom_sink();// BOTTOM SINK
        translate([wizWidth-3.5,3.5,0]) bottom_sink();
        translate([3.5,wizLength-3.5,0]) bottom_sink();
        translate([wizWidth-3.5,wizLength-3.5,0]) bottom_sink();        
    }
    translate([-10-boxThick,wizLength/2-8,2]) wall();// WALL MOUNT
    translate([wizWidth+boxThick,wizLength/2-8,2]) wall();
    translate([3.5,3.5,boxThick]) bottom_mount();// BOTTOM SINK
    translate([wizWidth-3.5,3.5,boxThick]) bottom_mount();
    translate([3.5,wizLength-3.5,boxThick]) bottom_mount();
    translate([wizWidth-3.5,wizLength-3.5,boxThick]) bottom_mount();
}

//-----------------

module wiz() {
    color("darkgreen")
    difference(){
        cube([wizWidth,wizLength,wizThick]);
        translate([3.5,3.5,-1]) wiz_hole();
        translate([wizWidth-3.5,3.5,-1]) wiz_hole();
        translate([3.5,wizLength-3.5,-1]) wiz_hole();
        translate([wizWidth-3.5,wizLength-3.5,-1]) wiz_hole();
    }
    color("black")
        translate([8,wizLength+3.6-21.20,wizThick]) cube([15.9,21.20,13.30]);//ETH
    color("black")
        translate([(wizWidth-30.8)/2,-12.55/2,wizThick]) cube([30.8,18.2,12.55]);//RS232
    color("black")
        translate([wizWidth-7.7-8.8,wizLength+3.6-13.2,wizThick]) cube([8.8,13.2,10.8]);//PWR
}

//-----------------

//bottom();
//translate([0,0,boxThick+bottomMountHeight]) wiz();
//translate([0,0,boxHeight+bottomMountHeight+wizThick]) top();
top();