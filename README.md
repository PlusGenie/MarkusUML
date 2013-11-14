What is the **MarkusUML**?
==========================

The MarkusUML extension allows to add UML diagram into Mediawiki using
**Quick Sequence Diagram Editor**.

What is Quick Sequence Diagram Editor?
======================================

Quick Sequence Diagram Editor is a tool for creating UML sequence
diagrams from textual descriptions of objects and messages that follow a
very easy syntax. This program was made by Markus Strauch. Please check
out [here][]

Install Quick Sequence Diagram Editor into the server
=====================================================

-   Download sdedit-4.0.1.jar from here:
    :   <http://sourceforge.net/projects/sdedit/files/latest/download?source=files>

-   Place it into *extensions/MarkusUML/* as follows:

<!-- -->

    bash-4.2$ tree extensions/MarkusUML/
    extensions/MarkusUML/
    |-- MarkusUML.php
    |-- README
    `-- sdedit-4.01.jar

    0 directories, 4 files

Update **LocalSettings.php** of Mediawiki
=========================================

-   Please add two lines into **LocalSettings.php**:
    :   \$wgAllowImageTag = true;
    :   require\_once( "\$IP/extensions/MarkusUML/MarkusUML.php" );

How to Use
==========

-   You should use the markups - \<uml\> \</uml\>

<!-- -->


    <uml>
    bfs:BFS[a]
    /queue:FIFO
    someNode:Node
    node:Node
    adjList:List
    adj:Node
    bfs:queue.new
    bfs:someNode.setLevel(0)
    bfs:queue.insert(someNode)
    [c:loop while queue != ()]
      bfs:node=queue.remove()
      bfs:level=node.getLevel()
      bfs:adjList=node.getAdjacentNodes()
      [c:loop 0 <= i < #adjList]
        bfs:adj=adjList.get(i)
        bfs:nodeLevel=adj.getLevel()
        [c:alt nodeLevel IS NOT defined]
          bfs:adj.setLevel(level+1)
          bfs:queue.insert(adj)
          --[else]
          bfs:nothing to do
        [/c]
      [/c]
    [/c]
    bfs:queue.destroy()
    </uml>

  [here]: http://sdedit.sourceforge.net

Credits
=======
-  MarkusUML is heavily based on the fantastic PlantUML, please check out README.PlantUML in the git.
