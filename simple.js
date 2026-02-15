// Variables and Arrays (VA) Examples in JavaScript

// 1. Variable Declaration
let name = "Alice";
const age = 25;
var city = "New York";

console.log(name, age, city);

// 2. Different Data Types
let stringVar = "Hello";
let numberVar = 42;
let booleanVar = true;
let undefinedVar;
let nullVar = null;

console.log(typeof stringVar);
console.log(typeof numberVar);
console.log(typeof booleanVar);

// 3. Variable Assignment and Update
let x = 10;
x = 20;
x += 5;
console.log("x =", x);

// 4. Array Declaration
let fruits = ["Apple", "Banana", "Orange"];
let numbers = [1, 2, 3, 4, 5];
let mixedArray = [1, "hello", true, null];

// 5. Accessing Array Elements
console.log("First fruit:", fruits[0]);
console.log("Second number:", numbers[1]);

// 6. Array Methods
fruits.push("Mango");
console.log("After push:", fruits);

let removed = fruits.pop();
console.log("Removed:", removed);

// 7. Array Length
console.log("Array length:", fruits.length);

// 8. Loop through Array
for (let i = 0; i < fruits.length; i++) {
  console.log("Fruit:", fruits[i]);
}

// 9. Array forEach
numbers.forEach(num => {
  console.log("Number:", num * 2);
});

// 10. Array map
let squared = numbers.map(n => n * n);
console.log("Squared numbers:", squared);

// 11. Array filter
let even = numbers.filter(n => n % 2 === 0);
console.log("Even numbers:", even);

// 12. String Variable Operations
let message = "JavaScript";
console.log("Length:", message.length);
console.log("Uppercase:", message.toUpperCase());
console.log("Lowercase:", message.toLowerCase());