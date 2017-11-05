//
// WIZ110SR Case
//

$fn=50;

//-----------------

boxThick=2;
wizLength=63;
wizWidth=45;
wizThick=1.5;
bottomMountHeight=1.3;
boxHeight=boxThick+bottomMountHeight+wizThick+13.30/2;

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
        translate([0,0,-boxHeight/2]) cylinder(h=boxHeight, d=2);
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

module vent(){
    translate([0.5,0.5,0])
    rounded_rect(wizWidth/2,1,boxThick+2,0.5);    
}

//-----------------

module top() {
    difference(){
        translate([0,0,boxHeight]) rounded_rect(wizWidth,wizLength,boxHeight,2);// BASE
        translate([0,0,boxHeight-boxThick]) cube([wizWidth,wizLength,boxHeight]);// FILLER
        translate([1,1,boxHeight-1]) rounded_rect(wizWidth-boxThick,wizLength-boxThick,2,2);// LIP-LOCK
        translate([0,0,boxThick+bottomMountHeight]) wiz();// WIZZ
        for (offset=[0,8,-8,16,-16])// VENT
        translate([wizWidth/4,wizLength/2-1+offset,2*boxHeight-boxThick-1]) vent();
    }
    translate([3.5,3.5,boxThick+bottomMountHeight+wizThick]) top_spacer();// TOP SPACER
    translate([wizWidth-3.5,3.5,boxThick+bottomMountHeight+wizThick]) top_spacer();
    translate([3.5,wizLength-3.5,boxThick+bottomMountHeight+wizThick]) top_spacer();
    translate([wizWidth-3.5,wizLength-3.5,boxThick+bottomMountHeight+wizThick]) top_spacer();
}

//-----------------

module bottom() {
    difference(){
            union() {
            rounded_rect(wizWidth,wizLength,boxHeight,2);// BASE
            translate([1,1,boxHeight-0.01])// LIP-LOCK
            difference(){
                rounded_rect(wizWidth-2,wizLength-2,1,2);
                translate([1,1,-1])rounded_rect(wizWidth-4,wizLength-4,3,2);
            }
        }
        translate([0,0,boxThick]) cube([wizWidth,wizLength,boxHeight]);// FILLER
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
    translate([-10-boxThick,wizLength/2-8,1]) wall();// WALL MOUNT
    translate([wizWidth+boxThick,wizLength/2-8,1]) wall();
    translate([3.5,3.5,boxThick]) bottom_mount();// BOTTOM MOUNT
    translate([wizWidth-3.5,3.5,boxThick]) bottom_mount();
    translate([3.5,wizLength-3.5,boxThick]) bottom_mount();
    translate([wizWidth-3.5,wizLength-3.5,boxThick]) bottom_mount();
}

//-----------------

module wiz() {
    color("darkgreen")
    difference(){
        cube([wizWidth,wizLength,wizThick]);// PCB
        translate([3.5,3.5,-1]) wiz_hole();// MOUNT HOLE
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

bottom();
translate([0,0,boxThick+bottomMountHeight]) wiz();
top();

